<?php


namespace App\Modules\Permissions\Repository;

use App\Shared\Database\Database;


class PermissionsRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function findAll(): array
    {
        return $this->db->select(
            "permissions p
            LEFT JOIN users u on p.created_by = u.id
            LEFT JOIN users u2 on p.updated_by = u2.id
            LEFT JOIN resources r on r.id = p.resource_id
            ",
            [],
            [
                'p.uuid',
                'r.name as resource',
                'p.action',
                'p.description',
                'p.created_at',
                'p.is_system',
                'u.username as created_by',
                'u2.username as updated_by',
            ]
        );
    }

    public function findByUuid($uuids): ?array
    {

        $isMulti = is_array($uuids);

        $whereClause = $isMulti ? [['role_id', 'IN', $uuids]] : ["uuid" => $uuids];

        $result = $this->db->select(
            "permissions p
            LEFT JOIN users u on p.created_by = u.id
            LEFT JOIN users u2 on p.updated_by = u2.id
            LEFT JOIN resources r on r.id = p.resource_id
            ",
            $whereClause,
            [
                'p.id',
                'p.uuid',
                'r.name as resource',
                'p.action',
                'p.description',
                'p.created_at',
                'p.is_system',
                'u.username as created_by',
                'u2.username as updated_by',
            ]
        );


        $return = $isMulti ? $result : $result[0];

        return $return ?? null;

    }

    public function findByName($resource, $action): ?array
    {
        $result = $this->db->select(
            "permissions p
            LEFT JOIN resources r on r.id = p.resource_id
            ",
            [
                'r.uuid' => $resource,
                'p.action' => $action
            ],
            ['p.id', 'p.uuid', 'r.name as resource', 'p.action', 'p.description', 'p.created_at', 'p.is_system']
        );


        return $result[0] ?? null;


    }

    public function create($permission): ?int
    {

        $id = $this->db->insert(
            "permissions",
            [
                'uuid' => $permission['uuid'],
                'resource_id' => $permission['resource_id'],
                'action' => $permission['action'],
                'description' => $permission['description'],
                'created_at' => $permission['created_at'],
                'is_system' => $permission['is_system'],
            ]);

        return $id ?? null;

    }

    public function update(int $permissionID, array $updatedPermission): int
    {
        return $this->db->update(
            'permissions',
            $updatedPermission,
            ['id' => $permissionID]
        );


    }

    public function delete($id): int
    {

        return $this->db->delete(
            'permissions',
            ["id" => $id]
        );

    }

    public function getResourceIdByUUID($uuid): ?array
    {

        return $this->db->select(
            "resources",
            ["uuid" => $uuid],
            [
                "id"
            ]
        )[0];
    }


    public function getResources(): ?array
    {
        return $this->db->select(
            "resources",
            [],
            [
                "uuid",
                "name",
                "display_name",
                "description",
                "is_active",
                "created_at",
                "updated_at",

            ]
        );
    }
}
