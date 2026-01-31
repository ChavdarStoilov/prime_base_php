<?php
namespace App\Modules\Auth\Service;

use App\Modules\Users\Controller\Domain\User;
use App\Modules\Users\Repository\UserRepository;

class AuthService
{

    private UserRepository $repository;
    public function __construct(UserRepository $repository) {
        $this->repository = $repository;
    }

    public function authenticate(string $username, string $password): ?User
    {
        $user = $this->repository->findByUsername($username);

        if (!$user) {
            return null;
        }

        if (password_verify($password, $user->getPassword())) {
            return $user;
        }

        return null;
    }
}
