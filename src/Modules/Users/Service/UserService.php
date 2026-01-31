<?php

namespace App\Modules\Users\Service;

use App\Modules\Users\Controller\Domain\User;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Exception\ConflictException;
use App\Shared\Logger\Logger;
use Random\RandomException;
use Ramsey\Uuid\Uuid;

class UserService
{
    private UserRepository $repository;

    public function __construct(UserRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listUsers(): array
    {

        $users = $this->repository->getAllUsers();

        if (!$users) {
            throw new NotFoundException('Users not found');
        }

        return $users;

    }

    public function getUser(string $uuid): array
    {

        $existingUser = $this->repository->findByUuid($uuid);

        if (!$existingUser) {
            throw new NotFoundException('User not found');
        }

        return $existingUser->toPublicArray();
    }

    /**
     * @throws \Exception
     */
    public function createUser(array $data): User
    {
        if (empty($data['username']) || empty($data['password'])) {
            throw new ValidationException('Username and password are required');
        }

        if (strlen($data['username']) < 3) {
            throw new ValidationException('Username too short');
        }

        $uuid = $this->generateUuid();


        $hashedPassword = password_hash(
            $data['password'],
            PASSWORD_ARGON2ID
        );

        $status = 0;
        $role_id = 0;
        $createdAt = new \DateTimeImmutable();

        $newRecord = [
            'uuid' => $uuid,
            'username' => $data['username'],
            'password' => $hashedPassword,
            'is_active' => $status,
            'role_id' => $role_id,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];


        try {
            return $this->repository->createUser($newRecord);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '23000') !== false) {
                throw new ConflictException("Username already exists.");
            }
            throw $e;
        }
    }


    public function updateUserByUuid(string $uuid, array $data): array
    {
        $existingUser = $this->repository->findByUuid($uuid);

        if (!$existingUser) {
            throw new NotFoundException('User not found');
        }

        if (isset($data['username']) && $data['username'] !== $existingUser->getUsername()) {
            throw new ValidationException('Cannot update username');
        }

        if (isset($data['password'])) {
            if (empty($data['password'])) {
                throw new ValidationException('Password cannot be empty');
            }
            $existingUser->setPassword(password_hash($data['password'], PASSWORD_DEFAULT));
        }

        if (isset($data['is_active'])) {
            $existingUser->setIsActive((bool)$data['is_active']);
        }

        if (isset($data['role_id'])) {
            $existingUser->setRoleId((int)$data['role_id']);
        }

        $userArray = $existingUser->toArray();
        $updateAt = new \DateTimeImmutable();

        $userArray['updated_at'] = $updateAt->format('Y-m-d H:i:s');

        $result = $this->repository->updateUser($userArray);

        if ($result === 0) {
            throw new ConflictException('User was not updated successfully');
        }

        return $existingUser->toPublicArray();
    }


    public function deleteUserByUuid(string $uuid): void
    {

        $existingUser = $this->repository->findByUuid($uuid);

        if (!$existingUser) {
            throw new NotFoundException('User not found');
        }

        $result = $this->repository->deleteUser($uuid);

        if ($result === 0) {
            throw new ConflictException('User was not deleted successfully');
        }


    }

    /**
     * @throws RandomException
     */
    private function generateUuid(): string
    {
        return Uuid::uuid4()->toString();
    }
}
