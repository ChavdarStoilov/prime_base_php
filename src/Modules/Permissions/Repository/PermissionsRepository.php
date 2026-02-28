<?php


namespace App\Modules\Permissions\Repository;

use App\Shared\Database\Database;
use function PHPUnit\Framework\isArray;


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
            "permissions",
            [],
            ['uuid', 'resource', 'action', 'description', 'created_at', 'is_system']
        );
    }

    public function findByUuid($uuids): ?array
    {

        $isMulti = isArray($uuids);

        $whereClause =  $isMulti? [['role_id', 'IN', $uuids] ] : ["uuid" => $uuids];

        $result = $this->db->select(
            "permissions",
            $whereClause,
            ['id', 'uuid', 'resource', 'action', 'description', 'created_at', 'is_system']
        );


        $return = $isMulti ? $result : $result[0];

        return $return ?? null;

    }

    public function findByName($resource, $action): ?array
    {
        $result = $this->db->select(
            "permissions",
            [
                'resource' => $resource,
                'action' => $action
            ],
            ['id', 'uuid', 'resource', 'action', 'description', 'created_at', 'is_system']
        );


        return $result[0] ?? null;


    }

    public function create($permission): ?int
    {

        $id = $this->db->insert(
            'permissions',
            [
                'uuid' => $permission['uuid'],
                'resource' => $permission['resource'],
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


}
