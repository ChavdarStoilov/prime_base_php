<?php

namespace App\Middleware;

use App\Modules\Auth\Service\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthService $authService
    )
    {
    }

    public function process(Request $request, Handler $handler): ResponseInterface
    {

        $identifier = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $route = $request->getUri()->getPath();

        $secondsLeft = $this->authService->checkRateLimit($identifier, $route);

        if ($secondsLeft > 0) {
            $response = new Response();
            $data = [
                'status' => 429,
                'error' => 'Too many attempts. Please try again later.',
                'retry_after' => $secondsLeft . 's'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withStatus(429)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Retry-After', (string)$secondsLeft);
        }

        return $handler->handle($request);
    }
}
