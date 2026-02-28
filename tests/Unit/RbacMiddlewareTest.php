<?php

use PHPUnit\Framework\TestCase;
use App\Middleware\RbacMiddleware;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\UnauthorizedException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

class RbacMiddlewareTest extends TestCase
{
    public function testUnauthorizedWithoutUserAttribute(): void
    {
        $repoMock = $this->createMock(UserRepository::class);
        $middleware = new RbacMiddleware($repoMock, ['create_user'], false);

        $request = $this->createMock(Request::class);
        $request->method('getAttribute')->with('user')->willReturn(null);

        $this->expectException(UnauthorizedException::class);

        $handler = $this->createMock(Handler::class);
        $middleware->process($request, $handler);
    }
}
