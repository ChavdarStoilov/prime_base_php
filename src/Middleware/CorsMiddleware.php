<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

final class CorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;

    public function __construct()
    {
        $origins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        $this->allowedOrigins = array_map('trim', explode(',', $origins));
    }

    public function process(Request $request, Handler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');
        $method = $request->getMethod();

        if ($method === 'OPTIONS') {
            $response = new \Slim\Psr7\Response();
            $response = $response->withStatus(200);
        } else {
            $response = $handler->handle($request);
        }

        if ($origin && (in_array($origin, $this->allowedOrigins) || in_array('*', $this->allowedOrigins))) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-CSRF-Token')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
