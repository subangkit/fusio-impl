<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2022 Christoph Kappestein <christoph.kappestein@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Fusio\Impl\Service\Action;

use Fusio\Engine\Context;
use Fusio\Engine\ProcessorInterface;
use Fusio\Engine\Repository;
use Fusio\Engine\Request;
use Fusio\Model\Backend\ActionExecuteRequest;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Request as HttpRequest;
use PSX\Record\Record;
use PSX\Uri\Uri;

/**
 * Executor
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org
 */
class Executor
{
    private ProcessorInterface $processor;
    private Repository\AppInterface $appRepository;
    private Repository\UserInterface $userRepository;

    public function __construct(ProcessorInterface $processor, Repository\AppInterface $appRepository, Repository\UserInterface $userRepository)
    {
        $this->processor      = $processor;
        $this->appRepository  = $appRepository;
        $this->userRepository = $userRepository;
    }

    public function execute(string|int $actionId, ActionExecuteRequest $request): mixed
    {
        $body = $request->getBody();
        if ($body === null) {
            $body = new Record();
        }

        $app  = $this->appRepository->get(1) ?? throw new \RuntimeException('App 1 not available');
        $user = $this->userRepository->get(1) ?? throw new \RuntimeException('User 1 not available');

        $uriFragments = $this->parseQueryString($request->getUriFragments());
        $parameters   = $this->parseQueryString($request->getParameters());
        $headers      = $this->parseQueryString($request->getHeaders());

        $uri = new Uri('/');
        $uri = $uri->withParameters($parameters);

        $httpRequest = new HttpRequest($uri, $request->getMethod() ?? 'GET', $headers);
        $httpContext = new HttpContext($httpRequest, $uriFragments);

        $context = new Context(0, '/', $app, $user);
        $request = new Request\HttpRequest($httpContext, $body);

        return $this->processor->execute($actionId, $request, $context);
    }

    private function parseQueryString(?string $data): array
    {
        $result = array();
        if (!empty($data)) {
            parse_str($data, $result);
        }
        return $result;
    }
}
