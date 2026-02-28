<?php

namespace App\Modules\Users\Domain;

class User
{

    private $userId;
    private string $uuid;
    private string $username;
    private string $password;
    private int $isActive;
    readonly string $createdAt;
    readonly string $updatedAt;

    public function __construct(
        int    $userId = 0,
        string $uuid = '',
        string $username = '',
        string $password = '',
        int    $isActive = 0,
        string $createdAt = '',
        string $updatedAt = ''
    )
    {
        $this->userId = $userId;
        $this->uuid = $uuid;
        $this->username = $username;
        $this->password = $password;
        $this->isActive = $isActive;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }


    public function getUserId(): int {
        return $this->userId;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function isActive(): int
    {
        return $this->isActive;
    }

    public function setPassword($newPassword): void
    {
        $this->password = $newPassword;

    }

    public function setIsActive($setActive): void
    {
        $this->isActive = $setActive;

    }


    public function toArray(): array
    {
        return [
            "uuid" => $this->uuid,
            "username" => $this->username,
            "password" => $this->password,
            "is_active" => $this->isActive
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'username' => $this->getUsername(),
            'is_active' => $this->isActive(),
            "created_at" => $this->createdAt,
            "updated_at" => $this->updatedAt

        ];
    }

    public function apiArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'username' => $this->getUsername(),
        ];
    }
}

