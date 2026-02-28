<?php
namespace App\Middleware;

use App\Shared\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Routing\RouteContext;
use Ramsey\Uuid\Uuid;

final class UuidMiddleware
{

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();

        $uuid = $route?->getArgument('uuid');

        if (!$uuid || !Uuid::isValid($uuid)) {
            throw new ValidationException('Invalid UUID format');
        }

        return $handler->handle(
            $request->withAttribute('uuid', $uuid)
        );
    }
}
