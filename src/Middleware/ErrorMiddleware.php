<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;
use App\Shared\Exception\ApplicationException;
use App\Shared\Exception\ExceptionMessageResolver;
use App\Shared\Logger\Logger;

final class ErrorMiddleware implements MiddlewareInterface
{
    private ExceptionMessageResolver $resolver;

    public function __construct(ExceptionMessageResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ApplicationException  $e) {
            Logger::log('API Error: ' . $e::class, $e->getMessage());

            $message = $this->resolver->resolve(
                $e->getErrorCode()
            );

            $payload = [
                'success' => false,
                'error' => [
                    "code" => $e->getErrorCode(),
                    'message' => $message
                ]
            ];

            $response = new Response($e->getHttpStatus());
            $response->getBody()->rewind();


            $response->getBody()->write(json_encode($payload, JSON_THROW_ON_ERROR));

            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Throwable $e) {

            Logger::log('System Error: ' . $e::class, $e->getMessage());

            $response = new Response(500);

            $payload = [
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'Internal server error'
                ]
            ];

            $response->getBody()->write(
                json_encode($payload, JSON_THROW_ON_ERROR)
            );

            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
