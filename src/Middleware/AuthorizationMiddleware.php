<?php
namespace App\Middleware;

use App\Modules\Users\Service\UserService;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use App\Shared\Logger\Logger;

final class AuthorizationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserService $userService
    ) {}

    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $currentUser = $request->getAttribute('current_user');

        Logger::log("current_user", $currentUser);
        if (!$currentUser) {
            return $this->unauthorized();
        }

        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        if (!$route) {
            return $this->forbidden();
        }

        $permission = $route->getName();

        if (!$permission) {
            return $handler->handle($request);
        }

        $userPermissions = $this->userService
            ->getPermissionsForUser($currentUser['id']);

        Logger::log("user permission", $userPermissions);

        if (
            !in_array($permission, $userPermissions, true) &&
            !$currentUser['is_superuser']
        ) {
            Logger::log("RBAC deny user {$currentUser['uuid']} -> {$permission}");
            return $this->forbidden();
        }

        return $handler->handle($request);
    }

    private function unauthorized(): ResponseInterface
    {
        $res = new Response();
        $res->getBody()->write(json_encode(['error' => 'Unauthorized']));
        return $res->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    private function forbidden(): ResponseInterface
    {
        $res = new Response();
        $res->getBody()->write(json_encode(['error' => 'Forbidden']));
        return $res->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
    }
}
