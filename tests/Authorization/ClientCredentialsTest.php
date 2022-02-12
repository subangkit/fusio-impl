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

namespace Fusio\Impl\Tests\Authorization;

use Fusio\Impl\Table\App\Token;
use Fusio\Impl\Tests\Fixture;
use PSX\Framework\Test\ControllerDbTestCase;
use PSX\Http\ResponseInterface;
use PSX\Json\Parser;

/**
 * ClientCredentialsTest
 *
 * @author  Christoph Kappestein <christoph.kappestein@gmail.com>
 * @license http://www.gnu.org/licenses/agpl-3.0
 * @link    https://www.fusio-project.org
 */
class ClientCredentialsTest extends ControllerDbTestCase
{
    public function getDataSet()
    {
        return Fixture::getDataSet();
    }

    public function testPost()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('Developer:qf2vX10Ec3wFZHx0K1eL'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        // if we provide no explicit scopes we get all scopes assigned to the user
        $this->assertAccessToken($response, 'backend,backend.account,backend.action,backend.app,backend.audit,backend.category,backend.config,backend.connection,backend.cronjob,backend.dashboard,backend.event,backend.log,backend.marketplace,backend.page,backend.plan,backend.rate,backend.role,backend.route,backend.schema,backend.scope,backend.sdk,backend.statistic,backend.transaction,backend.user,consumer,consumer.app,consumer.event,consumer.grant,consumer.log,consumer.page,consumer.plan,consumer.scope,consumer.subscription,consumer.transaction,consumer.user,authorization,foo,bar', 4);
    }

    public function testPostSpecificScope()
    {
        $body     = 'grant_type=client_credentials&scope=backend.action';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('Developer:qf2vX10Ec3wFZHx0K1eL'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        $this->assertAccessToken($response, 'backend.action', 4);
    }

    public function testPostEmail()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('developer@localhost.com:qf2vX10Ec3wFZHx0K1eL'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        $this->assertAccessToken($response, 'backend,backend.account,backend.action,backend.app,backend.audit,backend.category,backend.config,backend.connection,backend.cronjob,backend.dashboard,backend.event,backend.log,backend.marketplace,backend.page,backend.plan,backend.rate,backend.role,backend.route,backend.schema,backend.scope,backend.sdk,backend.statistic,backend.transaction,backend.user,consumer,consumer.app,consumer.event,consumer.grant,consumer.log,consumer.page,consumer.plan,consumer.scope,consumer.subscription,consumer.transaction,consumer.user,authorization,foo,bar', 4);
    }

    /**
     * As consumer we can not request an backend token
     */
    public function testPostConsumer()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('Consumer:qf2vX10Ec3wFZHx0K1eL'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        // we receive only the authorization scope since out user has not the backend scope
        $this->assertAccessToken($response, 'consumer,consumer.app,consumer.event,consumer.grant,consumer.log,consumer.page,consumer.plan,consumer.scope,consumer.subscription,consumer.transaction,consumer.user,authorization,foo,bar', 2);
    }

    /**
     * A deactivated user can not request a backend token
     */
    public function testPostDisabled()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('Disabled:qf2vX10Ec3wFZHx0K1eL'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        $body = (string) $response->getBody();

        $expect = <<<JSON
{
    "error": "invalid_client",
    "error_description": "Unknown credentials"
}
JSON;

        $this->assertEquals(401, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    /**
     * Request via app key and secret
     */
    public function testPostApp()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('5347307d-d801-4075-9aaa-a21a29a448c5:342cefac55939b31cd0a26733f9a4f061c0829ed87dae7caff50feaa55aff23d'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        // we receive only the authorization scope since out user has not the backend scope
        $this->assertAccessToken($response, 'authorization,foo,bar', 2, 3);
    }

    /**
     * A pending app can no request an access token
     */
    public function testPostAppPending()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('7c14809c-544b-43bd-9002-23e1c2de6067:bb0574181eb4a1326374779fe33e90e2c427f28ab0fc1ffd168bfd5309ee7caa'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        $body = (string) $response->getBody();

        $expect = <<<JSON
{
    "error": "invalid_client",
    "error_description": "Unknown credentials"
}
JSON;

        $this->assertEquals(401, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    /**
     * A deactivated app can no request an access token
     */
    public function testPostAppDeactivated()
    {
        $body     = 'grant_type=client_credentials';
        $response = $this->sendRequest('/authorization/token', 'POST', [
            'User-Agent'    => 'Fusio TestCase',
            'Authorization' => 'Basic ' . base64_encode('f46af464-f7eb-4d04-8661-13063a30826b:17b882987298831a3af9c852f9cd0219d349ba61fcf3fc655ac0f07eece951f9'),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ], $body);

        $body = (string) $response->getBody();

        $expect = <<<JSON
{
    "error": "invalid_client",
    "error_description": "Unknown credentials"
}
JSON;

        $this->assertEquals(401, $response->getStatusCode(), $body);
        $this->assertJsonStringEqualsJsonString($expect, $body, $body);
    }

    private function assertAccessToken(ResponseInterface $response, string $scope, int $userId, int $appId = 1)
    {
        $body = (string) $response->getBody();
        $data = Parser::decode($body, true);

        $this->assertEquals(200, $response->getStatusCode(), $body);

        $expireDate = strtotime('+2 days');

        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('token_type', $data);
        $this->assertEquals('bearer', $data['token_type']);
        $this->assertArrayHasKey('expires_in', $data);
        $this->assertEquals(date('Y-m-d H:i', $expireDate), date('Y-m-d H:i', $data['expires_in']));
        $this->assertArrayHasKey('scope', $data);
        $this->assertEquals($scope, $data['scope']);

        // check whether the token was created
        $row = $this->connection->fetchAssoc('SELECT app_id, user_id, status, token, scope, expire, date FROM fusio_app_token WHERE token = :token', ['token' => $data['access_token']]);

        $this->assertEquals($appId, $row['app_id']);
        $this->assertEquals($userId, $row['user_id']);
        $this->assertEquals(Token::STATUS_ACTIVE, $row['status']);
        $this->assertEquals($data['access_token'], $row['token']);
        $this->assertEquals($scope, $row['scope']);
        $this->assertEquals(date('Y-m-d H:i', $expireDate), date('Y-m-d H:i', strtotime($row['expire'])));
        $this->assertEquals(date('Y-m-d H:i'), substr($row['date'], 0, 16));
    }
}
