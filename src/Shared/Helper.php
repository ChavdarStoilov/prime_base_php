<?php


namespace App\Shared;

use JsonException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Ramsey\Uuid\Uuid;

class Helper {

    /**
     * @throws JsonException
     */
    public function json(Response $response, mixed $data, int $status = 200): Response
    {

        $payload = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );

        $response->getBody()->write($payload);

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    public function generateUuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    public function getCurrentUserID(Request $request): int {

        $jwtPayload = $request->getAttribute('current_user');

        return $jwtPayload['id'] ?? 0;

    }

}
