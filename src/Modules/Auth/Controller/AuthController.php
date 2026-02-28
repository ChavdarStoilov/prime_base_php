<?php

namespace App\Modules\Auth\Controller;

use App\Shared\Helper;
use App\Shared\Jwt\JwtService;
use App\Modules\Auth\Service\AuthService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class AuthController
{

    private AuthService $authService;
    private JwtService $jwtService;
    private Helper $helper;

    public function __construct(
        AuthService $authService,
        JwtService  $jwtService,
        Helper      $helper

    )
    {
        $this->helper = $helper;
        $this->authService = $authService;
        $this->jwtService = $jwtService;

    }

    /**
     * Login user and generate access + refresh token
     */
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->helper->json($response, ['error' => 'Username and password required'], 422);
        }

        $user = $this->authService->authenticate($username, $password);
        if (!$user) {
            return $this->helper->json($response, ['error' => 'Invalid credentials'], 401);
        }

        $accessToken = $this->jwtService->generate(['sub' => $user->getUuid()]);
        $refreshToken = $this->jwtService->generateRefreshToken($user->getUuid());

        return $this->helper->json($response, [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ]);

    }

    /**
     * Refresh access token using a refresh token
     */
    public function refresh(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody() ?? [];
        $refreshToken = $data['refresh_token'] ?? null;

        if (!$refreshToken) {
            return $this->helper->json($response, ['error' => 'Refresh token missing'], 400);
        }

        $userUUID = $this->jwtService->validateRefreshToken($refreshToken);
        if (!$userUUID) {
            return $this->helper->json($response, ['error' => 'Invalid or expired refresh token'], 401);
        }

        $accessToken = $this->jwtService->generate(['sub' => $userUUID]);

        return $this->helper->json($response, [
            'access_token' => $accessToken,
            'expires_in' => 3600
        ]);
    }
}
