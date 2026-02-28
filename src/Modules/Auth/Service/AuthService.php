<?php

namespace App\Modules\Auth\Service;

use App\Modules\Users\Domain\User;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\ValidationException;

class AuthService
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
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

        $response = $this->repository->findByUsername($username);
        if (!$response) {
            return null;
        }

        $user = new User([
                "id" => $response['id'],
                "uuid" => $response['uuid'],
                "username" => $response['username'],
                "password" => $response['password'] ?? null,
                "is_active" => (int)$response['is_active'],
            ]
        );

        if (password_verify($password, $user->getPassword())) {
            return $user;
        }

        return null;
    }
}
