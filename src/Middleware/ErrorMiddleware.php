<?php
namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Exception\UnauthorizedException;
use App\Shared\Exception\ConflictException;
use App\Shared\Logger\Logger;

final class ErrorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            Logger::log('API Error: ' . $e::class, $e->getMessage());

            $status = match (true) {
                $e instanceof ValidationException => 400,
                $e instanceof UnauthorizedException => 401,
                $e instanceof NotFoundException => 404,
                $e instanceof ConflictException => 409,
                default => 500,
            };

            $response = new Response($status);

            $payload = [
                'success' => false,
                'message' => ($_ENV['APP_ENV'] ?? 'production') === 'production'
                    ? 'Internal server error'
                    : $e->getMessage()
            ];

            $response->getBody()->write(json_encode($payload));


            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
