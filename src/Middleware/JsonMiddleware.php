<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class JsonMiddleware implements MiddlewareInterface
{
    public function process(Request $request, Handler $handler): Response
    {
        $response = $handler->handle($request);

        $statusCode = $response->getStatusCode();

        $body = (string) $response->getBody();
        $decodedBody = null;

        if (!empty($body)) {
            $decodedBody = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $response;
            }
        }

        $payload = [
            'status' => $statusCode,
            'date'   => date('Y-m-d H:i:s'),
            'data'   => $decodedBody
        ];

        $newResponse = $response
            ->withHeader('Content-Type', 'application/json');

        $newResponse->getBody()->rewind();
        $newResponse->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

        return $newResponse;
    }
}
