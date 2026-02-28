<?php


namespace App\Modules\Roles\Repository;

use App\Shared\Database\Database;
use RuntimeException;
use App\Shared\Logger\Logger;


class RolesRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }


    public function list(): array
    {
        return $this->db->select(
            "roles",
            [],
            ['uuid', 'name', 'description', 'created_at', 'updated_at', 'created_by', 'updated_by', 'is_active']
        );
    }

    public function findByUuid($id): ?array
    {
        $result = $this->db->select(
            "roles",
            [
                'uuid' => $id
            ],
            ['id', 'uuid', 'name', 'description', 'created_at', 'updated_at', 'created_by', 'updated_by', 'is_active']
        );

        return $result[0] ?? null;
    }

    public function findByName($name): ?array
    {
        $result = $this->db->select(
            "roles",
            [
                'name' => $name
            ],
            ['id', 'uuid', 'name', 'description', 'created_at', 'updated_at', 'created_by', 'updated_by', 'is_active']
        );

        return $result[0] ?? null;

    }

    public function create(array $role): ?int
    {

        $id = $this->db->insert(
            'roles',
            $role
        );

        return $id ?? null;

    }

    public function update(int $roleID, array $updatedRole): int
    {
        return $this->db->update(
            'roles',
            $updatedRole,
            ['id' => $roleID]
        );


    }

    public function delete(int $id): int
    {

        return $this->db->delete(
            'roles',
            ["id" => $id]
        );

    }

    // Pivot
    public function attachPermission(string $roleId, string $permissionId): void
    {
    }

    public function detachPermission(string $roleId, string $permissionId): void
    {
    }

    public function getRolePermissions(string $roleId): array
    {
    }


}
