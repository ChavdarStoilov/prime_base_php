<?php

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class AuthControllerTest extends TestCase
{
    private $app;

    protected function setUp(): void
    {
        $this->app = require __DIR__ . '/Bootstrap.php';
    }

    public function testLoginSuccess(): void
    {
        $body = ['username' => 'user1', 'password' => 'secret'];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/v1/auth/login')
            ->withBody((new StreamFactory())->createStream(json_encode($body)))
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);
        $data = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertArrayHasKey('access_token', $data);
        $this->assertArrayHasKey('refresh_token', $data);
    }

    public function testLoginInvalidCredentials(): void
    {
        $body = ['username' => 'user1', 'password' => 'wrongpass'];
        $request = (new ServerRequestFactory())->createServerRequest('POST', '/api/v1/auth/login')
            ->withBody((new StreamFactory())->createStream(json_encode($body)))
            ->withHeader('Content-Type', 'application/json');

        $response = $this->app->handle($request);
        $data = json_decode((string)$response->getBody(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Invalid credentials', $data['data']['error']);
    }
}
