<?php

namespace App\Modules\Auth\Repository;

use App\Shared\Database\Database;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;

class AuthRepository
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Create a refresh token for a user
     */
    public function createRefreshToken(array $refreshToken): int
    {

        try {
            return $this->db->insert('refresh_tokens', [
                'user_id' => $refreshToken['user_id'],
                'token' => $refreshToken['token'],
                'expires_at' => $refreshToken['expires_at'],
            ]);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '23000') !== false) {
                throw new ConflictException("Token already exists.");
            }
            throw $e;
        }
    }

    /**
     * Validate refresh token and return user UUID if valid
     */
    public function validateRefresh(string $refreshToken): ?string
    {
        $result = $this->db->select(
            'refresh_tokens r JOIN users u ON u.id = r.user_id',
            [
                'AND' => [
                    ['r.token', '=', $refreshToken],
                    ['r.revoked', '=', 0],
                    ['r.expires_at', '>', (new \DateTimeImmutable())->format('Y-m-d H:i:s')],
                    ['u.is_active', '=', 1]
                ]
            ],
            ['u.uuid'],
            '',
            1
        );


        return $result[0]['uuid'] ?? null;
    }

    public function storeRefreshToken(string $userUUID, string $token, int $expireSeconds): int
    {
        $result = $this->db->select('users', ['uuid' => $userUUID], ['id'], '', 1);
        if (!$result) {
            throw new NotFoundException('User not found.');
        }

        $userId = $result[0]['id'];
        $data = [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => (new \DateTimeImmutable("+$expireSeconds seconds"))->format('Y-m-d H:i:s'),
            'revoked' => 0,
        ];

        return $this->db->insert('refresh_tokens', $data);
    }

}
