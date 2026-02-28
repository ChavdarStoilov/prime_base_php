<?php

namespace App\Modules\Users\Service;

use App\Modules\Users\Domain\User;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ErrorCodes;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Helper;

class UserService
{
    private UserRepository $repository;
    private Helper $helper;

    public function __construct(
        UserRepository $repository,
        Helper         $helper
    )
    {
        $this->repository = $repository;
        $this->helper = $helper;
    }

    public function listUsers(): array
    {
        $users = $this->repository->getAllUsers();
        if (!$users) {
            return [];
        }

        $usersArray = [];
        foreach ($users as $user) {
            $tempUser = $this->mapToDomain($user);
            $usersArray[] = $tempUser->toPublicArray();
        }

        return $usersArray;
    }

    public function getUser(string $uuid, bool $isAuth = false): array|User
    {
        $existingUser = $this->repository->findByUUID($uuid);
        if (!$existingUser) {
            throw new NotFoundException(ErrorCodes::USER_NOT_FOUND);
        }
        $user = $this->mapToDomain($existingUser);
        return $isAuth ? $user : $user->toPublicArray();
    }


    public function createUser(array $data): array
    {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            throw new ValidationException(ErrorCodes::USERNAME_AND_PASSWORD_REQUIRED);
        }

        if (strlen($username) < 3) {
            throw new ValidationException(ErrorCodes::USERNAME_TOO_SHORT);
        }

        if ($this->repository->findByUsername($username)) {
            throw new ConflictException(ErrorCodes::USERNAME_ALREADY_EXISTS);
        }

        if (strlen($password) < 6) {
            throw new ValidationException(ErrorCodes::PASSWORD_TOO_SHORT);
        }

        $uuid = $this->helper->generateUuid();
        $hashedPassword = password_hash($password, PASSWORD_ARGON2ID);

        $createdAt = new \DateTimeImmutable();
        $newRecord = [
            'uuid' => $uuid,
            'username' => $username,
            'password' => $hashedPassword,
            'is_active' => 0,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];

        $response = $this->repository->createUser($newRecord);
        if (!$response) {
            throw new ConflictException(ErrorCodes::USER_NOT_CREATED);
        }

        return $this->mapToDomain($newRecord)->apiArray();
    }

    public function updateUserByUuid(string $uuid, array $data): array
    {

        if (key_exists('username', $data)) {
            throw new ValidationException(ErrorCodes::USERNAME_UPDATE_FORBIDDEN);
        }

        $existingUser = $this->repository->findByUUID($uuid);

        if (!$existingUser) {
            throw new NotFoundException(ErrorCodes::USER_NOT_FOUND);
        }

        $user = $this->mapToDomain($existingUser);


        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                throw new ValidationException(ErrorCodes::PASSWORD_TOO_SHORT);
            }
            $user->setPassword(password_hash($data['password'], PASSWORD_ARGON2ID));
        }

        if (isset($data['is_active'])) {
            $user->setIsActive((bool)$data['is_active']);
        }


        $userArray = $user->toArray();
        $updateAt = new \DateTimeImmutable();
        $userArray['updated_at'] = $updateAt->format('Y-m-d H:i:s');

        $result = $this->repository->updateUser($existingUser['id'], $userArray);
        if ($result === 0) {
            throw new ConflictException(ErrorCodes::USER_NOT_UPDATED);
        }

        $user = $this->mapToDomain(array_merge($existingUser, $userArray));

        return $user->apiArray();
    }

    public function deleteUserByUuid(string $uuid): void
    {
        $existingUser = $this->repository->findByUUID($uuid);

        if (!$existingUser) {
            throw new NotFoundException(ErrorCodes::USER_NOT_FOUND);
        }

        $result = $this->repository->deleteUser($existingUser['id']);

        if ($result === 0) {
            throw new ConflictException(ErrorCodes::USER_NOT_DELETED);
        }
    }


    private function mapToDomain(array $row): User
    {
        return new User(
            $row["id"] ?? 0,
            $row['uuid'],
            $row['username'],
            $row['password'] ?? '',
            (int)$row['is_active'],
            $row['created_at'] ?? '',
            $row['updated_at'] ?? ''
        );
    }
}
