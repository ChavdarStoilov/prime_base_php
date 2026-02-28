<?php


namespace App\Modules\Roles\Domain;


class Role
{
    private string $uuid;
    private string $name;
    private string $description;
    readonly int $createdBy;
    private int $updatedBy;
    readonly string $createdAt;
    private string $updatedAt;
    private int $isActive;

    public function __construct(array $data)
    {
        $this->uuid = $data['uuid'] ?? '';
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->createdBy = $data['created_by'] ?? 0;
        $this->updatedBy = $data['updated_by'] ?? 0;
        $this->createdAt = $data['created_at'] ?? '';
        $this->updatedAt = $data['updated_at'] ?? '';
        $this->isActive = $data['is_active'] ?? 0;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): string
    {
        return $this->name;
    }


    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIsActive(): int {
        return $this->isActive;
    }


    public function setActive($isActive): void
    {
        $this->isActive = $isActive;

    }


    public function toPublicArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'created_by' => $this->createdBy,
            "updated_by" => $this->updatedBy,
            "created_at" => $this->createdAt,
            "updated_at" => $this->updatedAt,
            "is_active" => $this->getIsActive()

        ];
    }

    public function apiArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'name' => $this->getName()
        ];
    }
}
