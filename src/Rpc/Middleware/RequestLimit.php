<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2021 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Impl\Rpc\Middleware;

use Fusio\Engine\Request\RpcRequest;
use Fusio\Impl\Framework\Loader\Context;
use Fusio\Impl\Service;
use PSX\Http\Exception as StatusCode;

/**
 * RequestLimit
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link    https://www.fusio-project.org/
 */
class RequestLimit
{
    /**
     * @var Service\Rate
     */
    private $rateService;

    /**
     * @var string
     */
    private $remoteIp;

    public function __construct(Service\Rate $rateService, string $remoteIp)
    {
        $this->rateService = $rateService;
        $this->remoteIp = $remoteIp;
    }

    public function __invoke(RpcRequest $request, Context $context)
    {
        $success = $this->rateService->assertLimit(
            $this->remoteIp,
            $context->getRouteId(),
            $context->getApp()
        );

        if (!$success) {
            throw new StatusCode\ClientErrorException('Rate limit exceeded', 429);
        }
    }
}
