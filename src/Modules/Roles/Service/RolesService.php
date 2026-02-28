<?php

namespace App\Modules\Roles\Service;

use App\Modules\Roles\Domain\Role;
use App\Modules\Roles\Repository\RolesRepository;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\ErrorCodes;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Helper;
use DateTimeImmutable;

class RolesService
{
    private RolesRepository $repository;

    private Helper $helper;

    public function __construct(
        RolesRepository $repository,
        Helper $helper
    )
    {
        $this->repository = $repository;
        $this->helper = $helper;
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


        $rolesArray = [];

        foreach ($rows as $row) {
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

    private function mapToDomain(array $row): Role
    {
        return new Role($row);
    }
}
