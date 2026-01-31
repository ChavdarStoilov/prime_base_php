<?php


namespace App\Middleware;

class JsonMiddleware
{
    public function __invoke($request, $handler)
    {
        $response = $handler->handle($request);
        return $response->withHeader('Content-Type', 'application/json');
    }
}
