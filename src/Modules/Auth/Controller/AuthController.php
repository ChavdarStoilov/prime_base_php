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

        if ($username === '' || $password === '') {
            return $this->helper->json($response, [
                'error' => 'Username and password required'
            ], 422);
        }

        $user = $this->authService->authenticate($username, $password);

        if (!$user) {
            return $this->helper->json($response, [
                'error' => 'Invalid credentials'
            ], 401);
        }

        if (!$user->isActive()) {
            return $this->helper->json($response, [
                'error' => 'Your account is not active.'
            ], 403);
        }

        $accessToken = $this->jwtService->generate([
            'sub' => $user->getUuid(),
            'iat' => time(),
            'exp' => time() + 900,
            'jti' => bin2hex(random_bytes(16))
        ]);

        $refreshToken = $this->jwtService->generateRefreshToken($user->getUuid());

        $csrfToken = bin2hex(random_bytes(32));

        $response = $response
            ->withAddedHeader(
                'Set-Cookie',
                "access_token={$accessToken}; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=900"
            )
            ->withAddedHeader(
                'Set-Cookie',
                "refresh_token={$refreshToken}; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=2592000"
            )
            ->withAddedHeader(
                'Set-Cookie',
                "csrf_token={$csrfToken}; Secure; SameSite=Strict; Path=/"
            );

        return $this->helper->json($response, [
            'expires_in' => 900,
            'permissions' => $user->getPermissions(),
            'is_superuser' => $user->getIsSuperuser(),
        ]);
    }

    /**
     * Refresh access token using a refresh token
     */
    public function refresh(Request $request, Response $response): Response
    {
        $cookies = $request->getCookieParams();
        $refreshToken = $cookies['refresh_token'] ?? null;
        $existingCsrfToken = $cookies['csrf_token'] ?? null;

        if (!$refreshToken) {
            return $this->helper->json($this->clearCookies($response), [
                'error' => 'Session expired'
            ], 401);
        }

        try {
            $userUUID = $this->jwtService->validateRefreshToken($refreshToken);

            if (!$userUUID) {
                return $this->helper->json($this->clearCookies($response), [
                    'error' => 'Invalid session'
                ], 401);
            }

            $accessToken = $this->jwtService->generate(['sub' => $userUUID]);
            $newRefreshToken = $this->jwtService->rotateRefreshToken($refreshToken, $userUUID);

            $csrfToken = $existingCsrfToken ?: bin2hex(random_bytes(32));

            $response = $response
                ->withAddedHeader('Set-Cookie', "access_token={$accessToken}; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=900")
                ->withAddedHeader('Set-Cookie', "refresh_token={$newRefreshToken}; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=2592000")
                ->withAddedHeader('Set-Cookie', "csrf_token={$csrfToken}; Secure; SameSite=Strict; Path=/");

            return $this->helper->json($response, [
                'message' => 'Token refreshed successfully',
                'expires_in' => 900
            ]);

        } catch (\Exception $e) {
            return $this->helper->json($this->clearCookies($response), [
                'error' => 'Session expired'
            ], 401);
        }
    }

    private function clearCookies(Response $response): Response
    {
        $pastDate = "Thu, 01 Jan 1970 00:00:00 GMT";
        $settings = "; HttpOnly; Secure; SameSite=Strict; Path=/; Max-Age=0; Expires={$pastDate}";

        return $response
            ->withHeader('Set-Cookie', "access_token=" . $settings)
            ->withAddedHeader('Set-Cookie', "refresh_token=" . $settings)
            ->withAddedHeader('Set-Cookie', "csrf_token=" . $settings);
    }


    public function logout(Request $request, Response $response): Response
    {
        $cookies = $request->getCookieParams();
        $refreshToken = $cookies['refresh_token'] ?? null;

        if ($refreshToken) {
            $this->jwtService->revokeToken($refreshToken);
        }

        return $this->clearCookies($response)
            ->withStatus(200);
    }
}
