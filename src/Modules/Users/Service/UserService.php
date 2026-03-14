<?php

namespace App\Modules\Users\Service;

use App\Modules\Roles\Service\RolesService;
use App\Shared\Logger\Logger;
use App\Modules\Users\Domain\User;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ErrorCodes;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Helper;
use Ramsey\Uuid\Uuid;

class UserService
{
    private UserRepository $repository;

    private RolesService $rolesService;
    private Helper $helper;

    public function __construct(
        UserRepository $repository,
        RolesService   $rolesRepository,
        Helper         $helper
    )
    {
        $this->repository = $repository;
        $this->helper = $helper;
        $this->rolesService = $rolesRepository;
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

        Logger::log("request", $data);

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


        Logger::log("request", $data);

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
            $is_active = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
            $user->setIsActive($is_active);
        }


        if (
            isset($data['role']) &&
            Uuid::isValid($data['role']) &&
            $user->getRoleUuid() != $data['role']
        ) {
            $role = $this->rolesService->assignRoleToUser($uuid, (array)$data['role']);
            Logger::log("role", $role);

            $user->setRole(
                [
                    "name" => $role['role_name'],
                    "uuid" => $role['role_uuid']
                ]
            );

        }

        $userArray = $user->toArray();
        $updateAt = new \DateTimeImmutable();
        $userArray['updated_at'] = $updateAt->format('Y-m-d H:i:s');

        if (empty($userArray['password'])) {
            unset($userArray['password']);
        }


        Logger::log("user update", $userArray);
        $result = $this->repository->updateUser($existingUser['id'], $userArray);
        if ($result === 0) {
            throw new ConflictException(ErrorCodes::USER_NOT_UPDATED);
        }

        $user = $this->mapToDomain(array_merge($existingUser, $userArray));

        return $user->toPublicArray();
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

    public function getPermissionsForUser($userId): array
    {
        $result = $this->repository->getPermissionsForUser($userId);

        $permissions = [];

        if (!$result) {
            return [];
        }

        foreach ($result as $permission) {
            $permissions[] = $permission['resource'] . "." . $permission['action'];
        }
        return $permissions;

    }


    private function mapToDomain(array $row): User
    {
        return new User([
            "id" => $row["id"] ?? 0,
            "uuid" => $row['uuid'],
            "username" => $row['username'],
            "password" => $row['password'],
            "is_active" => (int)$row['is_active'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            'role' => $row['role'],
            'role_uuid' => $row['role_uuid'],
            'is_superuser' => $row['is_superuser'],
        ]);
    }
}
