<?php
/*
 * PSX is a open source PHP framework to develop RESTful APIs.
 * For the current version and informations visit <http://phpsx.org>
 *
 * Copyright 2010-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Fusio\Impl\Rpc;

use Datto\JsonRpc\Exceptions\ApplicationException;
use Datto\JsonRpc\Exceptions\MethodException;
use Fusio\Engine\Context as EngineContext;
use Fusio\Engine\Processor;
use Fusio\Engine\Repository;
use Fusio\Engine\Request;
use Fusio\Impl\Loader\Context;
use Fusio\Impl\Service;
use Fusio\Impl\Table;
use PSX\Api\Resource;
use PSX\Framework\Config\Config;
use PSX\Http\Exception as StatusCode;
use PSX\Http\Exception\StatusCodeException;
use PSX\Http\RequestInterface;
use PSX\Record\Record;
use PSX\Record\RecordInterface;
use PSX\Schema\SchemaInterface;
use PSX\Schema\SchemaTraverser;
use PSX\Schema\ValidationException;
use PSX\Schema\Visitor\TypeVisitor;

/**
 * Evaluator
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    http://phpsx.org
 */
class Evaluator implements \Datto\JsonRpc\Evaluator
{
    /**
     * @var \Fusio\Engine\Processor
     */
    protected $processor;

    /**
     * @var \Fusio\Impl\Service\Security\TokenValidator
     */
    protected $tokenValidator;

    /**
     * @var \Fusio\Impl\Service\Rate
     */
    protected $rateService;

    /**
     * @var \Fusio\Impl\Service\Log
     */
    protected $logService;

    /**
     * @var \Fusio\Impl\Service\Plan\Payer
     */
    protected $planPayerService;

    /**
     * @var \Fusio\Impl\Table\Routes\Method 
     */
    protected $methodTable;

    /**
     * @var \Fusio\Impl\Table\Schema
     */
    protected $schemaTable;

    /**
     * @var \PSX\Framework\Config\Config
     */
    protected $config;

    /**
     * @var \PSX\Http\RequestInterface
     */
    protected $request;

    /**
     * @var \PSX\Schema\SchemaTraverser
     */
    protected $schemaTraverser;

    /**
     * @param \Fusio\Engine\Processor $processor
     * @param \Fusio\Impl\Service\Security\TokenValidator $tokenValidator
     * @param \Fusio\Impl\Service\Rate $rateService
     * @param \Fusio\Impl\Service\Log $logService
     * @param \Fusio\Impl\Table\Routes\Method $methodTable
     * @param \PSX\Framework\Config\Config $config
     * @param \PSX\Http\RequestInterface $request
     */
    public function __construct(Processor $processor, Service\Security\TokenValidator $tokenValidator, Service\Rate $rateService, Service\Log $logService, Service\Plan\Payer $planPayerService, Table\Routes\Method $methodTable, Table\Schema $schemaTable, Config $config, RequestInterface $request)
    {
        $this->processor        = $processor;
        $this->tokenValidator   = $tokenValidator;
        $this->rateService      = $rateService;
        $this->logService       = $logService;
        $this->planPayerService = $planPayerService;
        $this->methodTable      = $methodTable;
        $this->schemaTable      = $schemaTable;
        $this->config           = $config;
        $this->request          = $request;
        $this->schemaTraverser  = new SchemaTraverser();
    }

    /**
     * @inheritDoc
     */
    public function evaluate($method, $arguments)
    {
        try {
            return $this->execute($method, $arguments);
        } catch (StatusCodeException $e) {
            throw new ApplicationException($e->getMessage(), $e->getStatusCode());
        } catch (ValidationException $e) {
            throw new ApplicationException($e->getMessage(), 400);
        } catch (\Throwable $e) {
            throw new ApplicationException($e->getMessage(), 500);
        }
    }

    private function execute($methodName, $arguments)
    {
        $operationId = $methodName;
        $remoteIp    = $this->request->getAttribute('REMOTE_ADDR') ?: '127.0.0.1';

        $method = $this->methodTable->getMethodByOperationId($operationId);

        if (empty($method)) {
            throw new MethodException();
        }

        $context = new Context();
        $context->setRouteId($method['route_id']);
        $context->setMethod($method);

        $success = $this->tokenValidator->assertAuthorization(
            $method['method'],
            $this->request->getHeader('Authorization'),
            $context
        );

        if (!$success) {
            throw new StatusCode\UnauthorizedException('Could not authorize request', 'Bearer');
        }

        $success = $this->rateService->assertLimit(
            $remoteIp,
            $context->getRouteId(),
            $context->getApp()
        );

        if (!$success) {
            throw new StatusCode\ClientErrorException('Rate limit exceeded', 429);
        }

        $this->logService->log(
            $remoteIp,
            $method['method'],
            $this->request->getRequestTarget(),
            $this->request->getHeader('User-Agent'),
            $context,
            $this->request
        );

        // validate schema
        $body = new Record();
        if ($method['request'] > 0) {
            if (!isset($arguments['body'])) {
                throw new StatusCode\BadRequestException('No body provided');
            }

            // @TODO en/decode data since we need the JSON data as \stdClass and
            // not as array
            $data = \json_decode(\json_encode($arguments['body']));

            $schema = $this->getSchema($method['request']);
            if ($schema instanceof SchemaInterface) {
                $body = $this->schemaTraverser->traverse($data, $schema, new TypeVisitor());
            }
        }

        // execute action
        try {
            return $this->executeAction($arguments, $body, $method, $context);
        } catch (\Throwable $e) {
            $this->logService->error($e);

            throw $e;
        } finally {
            $this->logService->finish();
        }
    }

    private function getSchema($schemaId)
    {
        $row = $this->schemaTable->get($schemaId);

        if (isset($row['cache'])) {
            return Service\Schema::unserializeCache($row['cache']);
        } else {
            return null;
        }
    }
    
    private function executeAction(array $arguments, RecordInterface $body, $method, Context $context)
    {
        $baseUrl = $this->config->get('psx_url') . '/' . $this->config->get('psx_dispatch');
        $context = new EngineContext($method['route_id'], $baseUrl, $context->getApp(), $context->getUser());

        $rpcContext = new RpcContext(
            $method['method'],
            $arguments['headers'] ?? [],
            $arguments['uriFragments'] ?? [],
            $arguments['parameters'] ?? []
        );

        $request  = new Request($rpcContext, $body);
        $response = null;
        $actionId = $method['action'];
        $costs    = (int) $method['costs'];
        $cache    = $method['action_cache'];

        if ($costs > 0) {
            // as anonymous user it is not possible to pay
            if ($context->getUser()->isAnonymous()) {
                throw new StatusCode\ForbiddenException('This action costs points because of this you must be authenticated in order to call this action');
            }

            // in case the method has assigned costs check whether the user has
            // enough points
            $remaining = $context->getUser()->getPoints() - $costs;
            if ($remaining < 0) {
                throw new StatusCode\ClientErrorException('Your account has not enough points to call this action. Please purchase new points in order to execute this action', 429);
            }

            $this->planPayerService->pay($costs, $context);
        }

        if ($actionId > 0) {
            if ($method['status'] != Resource::STATUS_DEVELOPMENT && !empty($cache)) {
                // if the method is not in dev mode we load the action from the
                // cache
                $this->processor->push(Repository\ActionMemory::fromJson($cache));

                $response = $this->processor->execute($actionId, $request, $context);

                $this->processor->pop();
            } else {
                $response = $this->processor->execute($actionId, $request, $context);
            }
        } else {
            throw new StatusCode\ServiceUnavailableException('No action provided');
        }

        return $response->getBody();
    }
}
