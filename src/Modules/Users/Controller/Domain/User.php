<?php

namespace App\Modules\Users\Controller\Domain;

class User
{
    private int $id;
    private string $uuid;
    private string $username;
    private string $password;
    private int $isActive;
    private string $roleId;

    public function __construct(
        int    $id,
        string $uuid = '',
        string $username = '',
        string $password = '',
        int    $isActive = 0,
        int    $roleId = 0
    )
    {
        $this->id = $id;
        $this->uuid = $uuid;
        $this->username = $username;
        $this->password = $password;
        $this->isActive = $isActive;
        $this->roleId = $roleId;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getRoleId(): int
    {
        return $this->roleId;
    }

    public function setPassword($newPassword): void
    {
        $this->password = $newPassword;

    }

    public function setIsActive($setActive): void
    {
        $this->isActive = $setActive;

    }

    public function setRoleId(int $roleId): void
    {
        $this->roleId = $roleId;
    }

    public function toArray(): array
    {
        return [
            "user_id" => $this->id,
            "user_uuid" => $this->uuid,
            "username" => $this->username,
            "password" => $this->password,
            "is_active" => $this->isActive,
            "role_id" => $this->roleId
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'id'        => $this->getId(),
            'uuid'      => $this->getUuid(),
            'username'  => $this->getUsername(),
            'is_active' => $this->isActive(),
            'role_id'   => $this->getRoleId(),
        ];
    }
}

