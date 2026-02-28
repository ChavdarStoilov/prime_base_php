<?php
use PHPUnit\Framework\TestCase;
use Nyholm\Psr7\Factory\ServerRequestFactory;
use Nyholm\Psr7\Factory\StreamFactory;

final class UsersTest extends TestCase
{
    private $app;
    private $jwt;
    private $uuid;

    protected function setUp(): void
    {
        $bootstrap = require __DIR__ . '/Bootstrap.php';
        $this->app = $bootstrap['app'];
        $this->jwt = $bootstrap['jwt'];
        $this->uuid = $bootstrap['uuid'];
    }

    public function testListUsers(): void
    {
        $token = $this->jwt->generate([
            'sub' => $this->uuid,
            'permissions' => ['view_users']
        ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/v1/users')
            ->withHeader('Authorization', "Bearer $token");

        $response = $this->app->handle($request);
        $data = json_decode((string)$response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNotEmpty($data['data']);
        $this->assertEquals('testuser', $data['data'][0]['username']);
    }

    public function testGetUserNotFound(): void
    {
        $token = $this->jwt->generate([
            'sub' => $this->uuid,
            'permissions' => ['view_users']
        ]);

        $request = (new ServerRequestFactory())
            ->createServerRequest('GET', '/api/v1/users/00000000-0000-0000-0000-000000000000')
            ->withHeader('Authorization', "Bearer $token");

        $response = $this->app->handle($request);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
