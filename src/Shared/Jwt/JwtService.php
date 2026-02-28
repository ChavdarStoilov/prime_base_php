<?php

namespace App\Shared\Jwt;

use App\Modules\Auth\Repository\AuthRepository;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\ValidationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Random\RandomException;

final class JwtService
{
    private string $secret;
    private int $expire;
    private string $algorithm;
    private string $refreshTokenExpire;

    public function __construct(
        private AuthRepository $repo,
    )
    {
        $this->secret = $_ENV['JWT_SECRET'];
        $this->expire = (int)($_ENV['JWT_EXPIRE'] ?? 3600);
        $this->algorithm = $_ENV['JWT_ALGORITHM'];
        $this->refreshTokenExpire = (int)$_ENV['JWT_REFRESH_TOKEN_EXPIRE'] ?? 604800;
    }

    public function generate(array $payload): string
    {
        $time = time();
        $token = [
            ...$payload,
            'iat' => $time,
            'exp' => $time + $this->expire
        ];

        return JWT::encode($token, $this->secret, $this->algorithm);
    }

    public function validate(string $token): array
    {

        return (array)JWT::decode($token, new Key($this->secret, $this->algorithm));
    }

    /**
     * @throws RandomException
     */
    public function generateRefreshToken(string $userUUID): string
    {
        $token = bin2hex(random_bytes(64));
        $this->repo->storeRefreshToken($userUUID, $token, $this->refreshTokenExpire);
        return $token;
    }

    public function validateRefreshToken(string $token): ?string
    {

        try {
            return $this->repo->validateRefresh($token);
        } catch (\Exception $e) {
            throw new ValidationException('Invalid refresh token');

        }
    }
}
