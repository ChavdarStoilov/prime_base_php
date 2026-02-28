<?php

namespace App\Modules\Permissions\Service;

use App\Modules\Permissions\Domain\Permission;
use App\Modules\Permissions\Repository\PermissionsRepository;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ErrorCodes;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Helper;
use DateTimeImmutable;

class PermissionsService
{
    private PermissionsRepository $repository;

    private Helper $helper;

    public function __construct(
        PermissionsRepository $repository,
        Helper                $helper
    )
    {
        $this->repository = $repository;
        $this->helper = $helper;
    }

    public function getAll(): array
    {
        $rows = $this->repository->findAll();

        if (!$rows) {
            return [];
        }


        $permissionsArray = [];

        foreach ($rows as $row) {
            $domainRole = $this->mapToDomain($row)->toPublicArray();
            $permissionsArray[] = $domainRole;
        }


        return $permissionsArray;
    }

    public function getByUuid(string $uuid): array
    {
        $row = $this->repository->findByUuid($uuid);

        if (!$row) {
            throw new NotFoundException(ErrorCodes::PERMISSION_NOT_FOUND);
        }

        return $this->mapToDomain($row)->toPublicArray();
    }

    public function create(array $data, int $creatorUserId): array
    {
        if ($this->repository->findByName($data['resource'], $data['action'])) {
            throw new ConflictException(ErrorCodes::PERMISSION_ALREADY_EXISTS);
        }

        $uuid = $this->helper->generateUuid();
        $createdAt = new \DateTimeImmutable();

        $data['uuid'] = $uuid;
        $data['created_at'] = $createdAt->format('Y-m-d H:i:s');
        $data['created_by'] = $creatorUserId;

        $response = $this->repository->create($data);

        if (!$response) {
            throw new ConflictException(ErrorCodes::PERMISSION_NOT_CREATED);
        }

        return $this->mapToDomain($data)->ApiArray();
    }

    public function update(string $uuid, array $data, int $updaterUserId): array
    {
        $row = $this->repository->findByUuid($uuid);

        if (!$row) {
            throw new NotFoundException(ErrorCodes::PERMISSION_NOT_FOUND);
        }

        $data['updated_at'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['updated_by'] = $updaterUserId;

        $this->repository->update(
            (int)$row['id'],
            $data
        );

        $updatedRow = $this->repository->findByUuid($uuid);
        return $this->mapToDomain($updatedRow)->apiArray();
    }

    public function delete(string $uuid): void
    {
        $row = $this->repository->findByUuid($uuid);

        if ($row['is_system'] === 1) {
            throw new ValidationException(ErrorCodes::PERMISSION_CANNOT_UPDATED);
        }

        if (!$row) {
            throw new NotFoundException(ErrorCodes::PERMISSION_NOT_FOUND);
        }

        $result = $this->repository->delete((int)$row['id']);

        if ($result === 0) {
            throw new ConflictException(ErrorCodes::USER_NOT_DELETED);
        }
    }

    private function mapToDomain(array $row): Permission
    {
        return new Permission($row);
    }
}
