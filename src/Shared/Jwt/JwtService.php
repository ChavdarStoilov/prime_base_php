<?php

namespace App\Shared\Jwt;

use App\Modules\Auth\Repository\AuthRepository;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Shared\Logger\Logger;
use Random\RandomException;

final class JwtService
{
    private string $secret;
    private int $expire;

    public function __construct(
        private AuthRepository $repo,
        ?string $secret = null,
        ?int $expire = null
    ) {
        $this->secret = $secret ?? $_ENV['JWT_SECRET'];
        $this->expire = $expire ?? (int) ($_ENV['JWT_EXPIRE'] ?? 3600);
    }

    public function generate(array $payload): string
    {
        $time = time();
        $token = [
            ...$payload,
            'iat' => $time,
            'exp' => $time + $this->expire
        ];

        return JWT::encode($token, $this->secret, 'HS256');
    }

    public function validate(string $token): array
    {

        return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    /**
     * @throws RandomException
     */
    public function generateRefreshToken(int $userId): string
    {
        $token = bin2hex(random_bytes(64));
        $this->repo->storeRefreshToken($userId, $token);
        return $token;
    }

    public function validateRefreshToken(string $token): int
    {
        return $this->repo->validateRefresh($token);
    }
}
