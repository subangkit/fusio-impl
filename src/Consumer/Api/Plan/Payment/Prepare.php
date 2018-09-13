<?php
/*
 * Fusio
 * A web-application to create dynamically RESTful APIs
 *
 * Copyright (C) 2015-2018 Christoph Kappestein <christoph.kappestein@gmail.com>
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

namespace Fusio\Impl\Consumer\Api\Plan\Payment;

use Fusio\Impl\Authorization\Authorization;
use Fusio\Impl\Consumer\Api\ConsumerApiAbstract;
use Fusio\Impl\Consumer\Schema;
use PSX\Api\Resource;
use PSX\Http\Environment\HttpContextInterface;

/**
 * Prepare
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    http://fusio-project.org
 */
class Prepare extends ConsumerApiAbstract
{
    /**
     * @Inject
     * @var \Fusio\Impl\Service\Plan\Payment
     */
    protected $planPayment;
    
    /**
     * @inheritdoc
     */
    public function getDocumentation($version = null)
    {
        $resource = new Resource(Resource::STATUS_ACTIVE, $this->context->getPath());

        $resource->addMethod(Resource\Factory::getMethod('POST')
            ->setSecurity(Authorization::CONSUMER, ['consumer'])
            ->addResponse(200, $this->schemaManager->getSchema(Schema\Plan\Payment::class))
        );

        return $resource;
    }

    /**
     * @inheritdoc
     */
    protected function doPost($record, HttpContextInterface $context)
    {
        return $this->planPayment->prepare($context->getUriFragment('provider'), $context->getUriFragment('plan_id'));
    }
}
