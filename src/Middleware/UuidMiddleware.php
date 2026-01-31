<?php

namespace App\Middleware;

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

        if (!$uuid) {
            return $this->error('UUID is required', 400);
        }

        if (!Uuid::isValid($uuid)) {
            return $this->error('Invalid UUID format', 400);
        }

        return $handler->handle(
            $request->withAttribute('uuid', $uuid)
        );
    }

    private function error(string $msg, int $code): Response
    {
        $response = new ($code);
        $response->getBody()->write(json_encode(['error' => $msg]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}

