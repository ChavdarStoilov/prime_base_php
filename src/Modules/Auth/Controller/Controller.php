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
        private JwtService $jwtService
    ) {}

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
            $payload = ['error' => 'Invalid credentials'];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $token = $this->jwtService->generate([
            'sub' => $user->getUuid(),
        ]);

        $refreshToken = $this->jwtService->generateRefreshToken($user->getId());

        $payload = [
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Refresh access token using a refresh token
     */
    public function refresh(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            $payload = ['error' => 'Refresh token missing'];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        try {
            $userId = $this->jwtService->validateRefreshToken($refreshToken);
            $accessToken = $this->jwtService->generate([
                'sub' => $userId,
            ]);

            $payload = [
                'access_token' => $accessToken,
                'expires_in' => 3600
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $payload = ['error' => $e->getMessage()];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}
