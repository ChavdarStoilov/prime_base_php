<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $handler->handle($request);
        }

        $cookies = $request->getCookieParams();
        $csrfCookie = $cookies['csrf_token'] ?? null;
        $csrfHeader = $request->getHeaderLine('X-CSRF-Token');

        if (
            !$csrfCookie ||
            !$csrfHeader ||
            !hash_equals($csrfCookie, $csrfHeader)
        ) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'error' => 'CSRF mismatch'
            ]));

            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }

        return $handler->handle($request);
    }
}
