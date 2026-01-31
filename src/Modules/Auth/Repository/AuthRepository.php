<?php

namespace App\Modules\Auth\Repository;

use App\Shared\Database\Database;
use RuntimeException;

class AuthRepository
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::get();
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
                throw new RuntimeException("Token already exists.");
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
            'refresh_tokens r JOIN users u ON u.user_id = r.user_id',
            [
                'AND' => [
                    ['r.token', '=', $refreshToken],
                    ['r.revoked', '=', 0],
                    ['r.expires_at', '>', date('Y-m-d H:i:s')],
                ]
            ],
            ['u.user_id'],
            '',
            1
        );

        return $result[0]['user_id'] ?? null;
    }

    public function storeRefreshToken(int $userId, string $token, int $expireSeconds = 604800): int
    {
        $data = [
            'user_id'    => $userId,
            'token'      => $token,
            'expires_at' => date('Y-m-d H:i:s', time() + $expireSeconds),
            'revoked'    => 0,
        ];

        return $this->db->insert('refresh_tokens', $data);
    }

}
