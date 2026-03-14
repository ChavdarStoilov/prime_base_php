<?php

namespace App\Modules\Auth\Service;

use App\Modules\Auth\Repository\AuthRepository;
use App\Modules\Users\Domain\User;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\ValidationException;

class AuthService
{
    private UserRepository $userRepository;
    private AuthRepository $authRepository;

    public function __construct(
        UserRepository $userRepository,
        AuthRepository $authRepository
    )
    {
        $this->userRepository = $userRepository;
        $this->authRepository = $authRepository;
    }

    /**
     * Authenticate user by username and password
     */
    public function authenticate(string $username, string $password): ?User
    {
        $username = trim($username);
        if (empty($username) || empty($password)) {
            throw new ValidationException('Username and password are required');
        }

        $response = $this->userRepository->findByUsername($username);

        if (!$response) {
            return null;
        }

        $userPermissions = $this->userRepository->getPermissionsForUser($response['id']);

        $user = new User([
                "id" => $response['id'],
                "uuid" => $response['uuid'],
                "username" => $response['username'],
                "password" => $response['password'] ?? null,
                "is_active" => (int)$response['is_active'],
                "permissions" => $userPermissions,
                "is_superuser" => (int)$response['is_superuser'],
            ]
        );

        if (password_verify($password, $user->getPassword())) {
            return $user;
        }

        return null;
    }

    public function checkRateLimit(string $identifier, string $route): int
    {
        $maxAttempts = (int)($_ENV['AUTH_RATE_LIMIT_MAX'] ?? 5);
        $decayMinutes = (int)($_ENV['AUTH_RATE_LIMIT_DECAY'] ?? 1);

        $now = time();
        $window = $decayMinutes * 60;
        $record = $this->authRepository->getRateLimit($identifier, $route);

        if (!$record) {
            $this->authRepository->createRateLimit([
                'identifier' => $identifier,
                'route' => $route,
                'hits' => 1,
                'last_hit' => $now
            ]);
            return 0;
        }

        if ($now - (int)$record['last_hit'] > $window) {
            $this->authRepository->updateRateLimit((int)$record['id'], [
                'hits' => 1,
                'last_hit' => $now
            ]);
            return 0;
        }

        if ((int)$record['hits'] >= $maxAttempts) {
            return ((int)$record['last_hit'] + $window) - $now;
        }

        $this->authRepository->updateRateLimit((int)$record['id'], [
            'hits' => (int)$record['hits'] + 1,
            'last_hit' => $now
        ]);

        return 0;
    }

}
