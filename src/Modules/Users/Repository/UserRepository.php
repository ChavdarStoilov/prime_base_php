<?php

namespace App\Modules\Users\Repository;

use App\Shared\Database\Database;
use App\Shared\Exception\ConflictException;
use Exception;

class UserRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }


    /**
     * @return array
     */
    public function getAllUsers(): array
    {
        return $this->db->select(
            "users",
            [],
            ['uuid', 'username', 'is_active', 'created_at', 'updated_at']
        );
    }

    /**
     * @param array $user
     * @return int|null
     * @throws Exception
     */
    public function createUser(array $user): ?int
    {

        try {

            $id = $this->db->insert('users', [
                'uuid' => $user['uuid'],
                'username' => $user['username'],
                'password' => $user['password'],
                'is_active' => $user['is_active'],
                'created_at' => $user['created_at'],
            ]);

            return $id ?? null;

        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '23000')) {
                throw new ConflictException("Username already exists.");
            }
            throw $e;
        }
    }


    /**
     * Find user by username
     * @param string $username
     * @return array|null
     */


    public function findByUsername(string $username): ?array
    {
        $result = $this->db->select(
            'users',
            [
                'username' => $username
            ],
            ['id', 'uuid', 'username', 'password', 'is_active'],
            '',
            1
        );

        return $result[0] ?? null;

    }


    /**
     * @param string $uuid
     * @return array|null
     */
    public function findByUUID(string $uuid): ?array
    {
        $result = $this->db->select(
            'users',
            [
                "uuid" => $uuid
            ],
            '*',
            '',
            1
        );

        return $result[0] ?? null;

    }

    /**
     * @param $userID
     * @param array $updatedUser
     * @return int
     */
    public function updateUser($userID, array $updatedUser): int
    {

        return $this->db->update('users',
            $updatedUser,
            ['id' => $userID]
        );
    }


    /**
     * @param string $userID
     * @return int
     */
    public function deleteUser(string $userID): int
    {

        return $this->db->delete(
            'users',
            ["id" => $userID]
        );

    }
}
