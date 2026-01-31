<?php

namespace App\Modules\Users\Repository;

use App\Shared\Database\Database;
use App\Modules\Users\Controller\Domain\User;
use RuntimeException;
use App\Shared\Logger\Logger;

class UserRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::get();
    }


    public function getAllUsers()
    {
        return $this->db->select(
            "users",
            [],
            ['user_uuid', 'username', 'is_active', 'role_id', 'created_at', 'updated_at']
        );
    }

    /**
     * Create a new user
     */
    public function createUser(array $user): User
    {

        $id = $this->db->insert('users', [
            'user_uuid' => $user['uuid'],
            'username' => $user['username'],
            'password' => $user['password'],
            'is_active' => $user['is_active'],
            'role_id' => $user['role_id'],
            'created_at' => $user['created_at'],
        ]);


        return new User($id, $user['uuid'], $user['username']);


    }

    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?User
    {
        $result = $this->db->select(
            'users',
            [
                'username' => $username
            ],
            ['user_id', 'user_uuid', 'username', 'password'],
            '',
            1
        );

        if (empty($result)) {
            return null;
        }

        $row = $result[0];

        return new User(
            $row['user_id'],
            $row['user_uuid'],
            $row['username'],
            $row['password'] ?? null
        );
    }


    /**
     * @param string $uuid
     * @return User|null
     */
    public function findByUuid(string $uuid): ?User
    {
        $result = $this->db->select(
            'users',
            [
                "user_uuid" => $uuid
            ],
            '*',
            '',
            1
        );

        if (empty($result)) {
            return null;
        }

        $row = $result[0];

        return new User(
            $row['user_id'],
            $row['user_uuid'],
            $row['username'],
            $row['is_active'],
            $row['role_id']
        );
    }

    public function updateUser(array $updatedUser): int
    {

        return $this->db->update('users',
            $updatedUser,
            ['user_id' => $updatedUser['user_id']]
        );
    }

    public function deleteUser(string $userUuid): string
    {

        return $this->db->delete(
            'users',
            ["user_uuid" => $userUuid]
        );

    }
}
