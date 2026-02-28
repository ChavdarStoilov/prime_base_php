<?php


namespace App\Modules\Permissions\Domain;


class Permission
{

    private string $uuid;
    private string $resource;
    private string $action;
    private string $description;
    readonly string $createdAt;
    private int $isSystem;

    public function __construct(array $data)
    {

        $this->uuid = $data['uuid'] ?? '';
        $this->resource = $data['resource'] ?? '';
        $this->action = $data['action'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->createdAt = $data['created_at'] ?? '';
        $this->isSystem = $data['is_system'] ?? 0;
    }


    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getResource(): string
    {
        return $this->resource;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIsSystem(): int {
        return $this->isSystem;
    }


    public function setIsSystem($isSystem): void
    {
        $this->isSystem = $isSystem;

    }


    public function toPublicArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'resource' => $this->getResource(),
            'action' => $this->getAction(),
            'description' => $this->getDescription(),
            "created_at" => $this->createdAt,
            "is_system" => $this->getIsSystem()

        ];
    }

    public function apiArray(): array
    {
        return [
            'uuid' => $this->getUuid(),
            'resource' => $this->getResource(),
            'action' => $this->getAction(),
        ];
    }

}
