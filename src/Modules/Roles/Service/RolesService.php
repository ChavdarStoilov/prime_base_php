<?php

namespace App\Modules\Roles\Service;

use App\Modules\Permissions\Repository\PermissionsRepository;
use App\Modules\Roles\Domain\Role;
use App\Modules\Roles\Repository\RolesRepository;
use App\Modules\Users\Repository\UserRepository;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ErrorCodes;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Helper;
use App\Shared\Logger\Logger;
use Couchbase\LookupCountSpec;
use DateTimeImmutable;

class RolesService
{
    private RolesRepository $repository;

    private PermissionsRepository $permissionsRepo;
    private UserRepository $userRepo;
    private Helper $helper;

    public function __construct(
        RolesRepository       $repository,
        PermissionsRepository $permissionsRepo,
        UserRepository        $userRepo,
        Helper                $helper
    )
    {
        $this->repository = $repository;
        $this->permissionsRepo = $permissionsRepo;
        $this->helper = $helper;
        $this->userRepo = $userRepo;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        $rows = $this->repository->list();

        if (!$rows) {
            return [];
        }


        $rolesTemp = [];
        $rolesArray = [];


        foreach ($rows as $row) {
            $uuid = $row['uuid'];

            if (!isset($rolesTemp[$uuid])) {
                $rolesTemp[$uuid] = [
                    'uuid' => $row['uuid'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'created_by' => $row['created_by'],
                    'updated_by' => $row['updated_by'],
                    'is_active' => $row['is_active'],
                    'is_system' => $row['is_system'],
                    'permissions' => []
                ];
            }

            if ($row['permission_uuid']) {
                $rolesTemp[$uuid]['permissions'][] = [
                    'uuid' => $row['permission_uuid'],
                    'resource' => $row['permission_resource'],
                    'action' => $row['permission_action'],
                    'description' => $row['permission_description'],
                ];
            }
        }

        foreach ($rolesTemp as $row) {
            $domainRole = $this->mapToDomain($row)->toPublicArray();
            $rolesArray[] = $domainRole;
        }


        return $rolesArray;
    }


    /**
     * @param string $uuid
     * @return array
     */
    public function getByUuid(string $uuid): array
    {
        $row = $this->repository->findByUuid($uuid);

        if (!$row) {
            throw new NotFoundException(ErrorCodes::ROLE_NOT_FOUND);
        }

        return $this->mapToDomain($row)->toPublicArray();
    }


    /**
     * @param array $data
     * @return array
     */
    public function create(array $data, int $creatorUserId): array
    {
        if ($this->repository->findByName($data['name'])) {
            throw new ValidationException(ErrorCodes::ROLE_ALREADY_EXISTS);
        }

        $data['uuid'] = $this->helper->generateUuid();
        $data['created_at'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['created_by'] = $creatorUserId;


        $response = $this->repository->create($data);

        if (!$response) {
            throw new ConflictException(ErrorCodes::ROLE_NOT_CREATED);
        }

        return $this->mapToDomain($data)->apiArray();
    }

    public function update(string $uuid, array $data, int $updaterUserId): array
    {
        $row = $this->repository->findByUuid($uuid);

        if (!$row) {
            throw new NotFoundException(ErrorCodes::ROLE_NOT_FOUND);
        }

        $data['updated_at'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['updated_by'] = $updaterUserId;

        unset($data['user_id']);

        $this->repository->update(
            (int)$row['id'],
            $data
        );

        return $this->mapToDomain($row)->apiArray();
    }

    public function delete(string $uuid): void
    {
        $row = $this->repository->findByUuid($uuid);
        if (!$row) {
            throw new NotFoundException(ErrorCodes::ROLE_NOT_FOUND);
        }

        if ((int)$row['is_system'] === 1) {
            throw new ValidationException("System roles cannot be deleted.");
        }

        $this->repository->delete((int)$row['id']);
    }

    public function assignRolePermissions(string $roleUUID, $permissionsUUID): ?bool
    {


        [$roleId, $permissionsIds] = $this->getRoleAndPermissionsIds($roleUUID, $permissionsUUID);

        foreach ($permissionsIds as $permission) {

            $permissionId = (int)$permission['id'];

            $isExist = $this->repository->getRolePermissions($roleId, $permissionId);

            if ($isExist) {
                throw new NotFoundException(ErrorCodes::ROLE_PERMISSION_NOT_ADDED);
            }

            $response = $this->repository->attachRolePermission(
                $roleId,
                $permissionId
            );

            if (!$response) {
                throw new NotFoundException(ErrorCodes::ROLE_PERMISSION_NOT_ADDED);
            }

        }

        return true;
    }

    public function deAssignRolePermissions(string $roleUUID, array $permissionsUUID): void
    {

        [$roleId, $permissionsIds] = $this->getRoleAndPermissionsIds($roleUUID, $permissionsUUID);

        foreach ($permissionsIds as $permission) {

            $permissionId = (int)$permission['id'];

            $isExist = $this->repository->getRolePermissions($roleId, $permissionId);

            if (!$isExist) {
                throw new NotFoundException(ErrorCodes::ROLE_PERMISSION_CANNOT_DELETE);
            }

            $this->repository->detachPermission($roleId, $permissionId);
        }
    }


    public function getRoleAndPermissionsIds(string $roleUUID, array $permissionsUUID): ?array
    {

        $roleId = $this->repository->findByUuid($roleUUID);
        if (!$roleId) {
            throw new NotFoundException(ErrorCodes::ROLE_NOT_FOUND);
        }

        $permissionsId = $this->permissionsRepo->findByUuid($permissionsUUID);

        if (!$permissionsId) {
            throw new NotFoundException(ErrorCodes::PERMISSION_NOT_FOUND);
        }

        return [(int)$roleId['id'], $permissionsId];
    }


    public function assignRoleToUser(string $userUUID, array $roleUUIDS): array
    {
        $user = $this->userRepo->findByUuid($userUUID);

        if (!$user) {
            throw new ValidationException(ErrorCodes::USER_NOT_FOUND);
        }

//        if ($user['is_active'] === 0) {
//            throw new ValidationException(ErrorCodes::User)
//        }

        Logger::log("roleUUIDS", $roleUUIDS);

        $roleId = $this->repository->findByUuid($roleUUIDS)[0];
        Logger::log("role", $roleId);
        Logger::log("user id", $user);

        $userId = (int)$user['id'];
        $roleId = $roleId['id'];

        $isExist = $this->repository->getUserRoles($userId, $roleId);

        if ($isExist) {
            throw new ValidationException(ErrorCodes::ROLE_ALREADY_ADDED);
        }

        Logger::log("for send", [
            "user_id" => $userId,
            "role_id" => $roleId
        ]);

        $result = $this->repository->assignRoleToUser(
            [
                "user_id" => $userId,
                "role_id" => $roleId
            ]
        );

        if (!$result) {
            throw new NotFoundException(ErrorCodes::ROLE_NOT_ADDED);
        }

        Logger::log("result", $result);

        return [
            "role_name" => $roleId['name'],
            "role_uuid" => $roleId['uuid']
        ];

//        foreach ($roleIds as $roleId) {
//
//            $roleId = (int)$roleId['id'];
//            $userId = (int)$user['id'];
//
//            if (!is_array($roleUUIDS)) {
//                $roleUUIDS = [
//                    "role_name" => (string)$roleId['name'],
//                    "role_uuid" => (string)$roleId['uuid'],
//                ];
//            }
//
//            $isExist = $this->repository->getUserRoles($userId, $roleId);
//
//            if ($isExist) {
//                throw new ValidationException(ErrorCodes::ROLE_ALREADY_ADDED);
//            }
//
//            $result = $this->repository->assignRoleToUser(
//                [
//                    "user_id" => $userId,
//                    "role_id" => $roleId
//                ]
//            );
//
//            if (!$result) {
//                throw new NotFoundException(ErrorCodes::ROLE_NOT_ADDED);
//            }
//        }

//        return $roleResponse;

    }

    public function deAssignRoleFromUser(string $userUUID, array $roleUUIDs): void
    {

        $user = $this->userRepo->findByUuid($userUUID);

        if (!$user) {
            throw new ValidationException(ErrorCodes::USER_NOT_FOUND);
        }

//        if ($user['is_active'] === 0) {
//            throw new ValidationException(ErrorCodes::User)
//        }


        $roleIds = $this->repository->findByUuid($roleUUIDs);

        foreach ($roleIds as $roleId) {

            $roleId = (int)$roleId['id'];
            $userId = (int)$user['id'];

            $isExist = $this->repository->getUserRoles($userId, $roleId);

            if (!$isExist) {
                throw new ValidationException(ErrorCodes::ROLE_NOT_ADDED);
            }

            $result = $this->repository->detachRoleToUser([
                "user_id" => $userId,
                "role_id" => $roleId
            ]);

            if (!$result) {
                throw new NotFoundException(ErrorCodes::ROLE_NOT_ADDED);
            }
        }

    }

    private function mapToDomain(array $row): Role
    {
        return new Role($row);
    }
}
