<?php

namespace App\Modules\Auth\Controller;

use App\Shared\Jwt\JwtService;
use App\Modules\Auth\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class Controller
{
    public function __construct(
        private AuthService $authService,
        private JwtService  $jwtService
    )
    {
    }

    /**
     * Login user and generate access + refresh token
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $user = $this->authService->authenticate($username, $password);

        if (!$user) {
            $response->getBody()->write(json_encode(['error' => 'Invalid credentials']));
            return $response->withStatus(401);
        }

        $token = $this->jwtService->generate([
            'sub' => $user->getUuid(),
        ]);

        $refreshToken = $this->jwtService->generateRefreshToken($user->getId());


        $response->getBody()->write(json_encode([
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ]));
        return $response->withStatus(200);

    }

    /**
     * Refresh access token using a refresh token
     */
    public function refresh(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {

            $response->getBody()->write(json_encode(['error' => 'Refresh token missing']));
            return $response->withStatus(400);

        }

        $userId = $this->jwtService->validateRefreshToken($refreshToken);
        $accessToken = $this->jwtService->generate([
            'sub' => $userId,
        ]);


        $response->getBody()->write(json_encode([
            'access_token' => $accessToken,
            'expires_in' => 3600
        ]));
        return $response->withStatus(200);
    }
}
