<?php

use App\Modules\Users\Repository\UserRepository;
use App\Modules\Users\Service\UserService;
use App\Shared\Exception\ConflictException;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\ValidationException;
use App\Shared\Helper;
use PHPUnit\Framework\TestCase;

class UsersServiceTest extends TestCase
{
    private UserService $service;
    private $repositoryMock;
    private $helperMock;

    protected function setUp(): void
    {
        $this->repositoryMock = $this->createMock(UserRepository::class);
        $this->helperMock = $this->createMock(Helper::class);
        $this->service = new UserService($this->repositoryMock, $this->helperMock);
    }

    public function testListUsersReturnsArray(): void
    {
        $this->repositoryMock->method('getAllUsers')->willReturn([
            ['uuid' => 'uuid1', 'username' => 'user1', 'is_active' => 1, 'created_at' => '2026-02-16 10:00:00']
        ]);

        $result = $this->service->listUsers();
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('uuid', $result[0]);
    }

    public function testGetUserThrowsNotFound(): void
    {
        $this->repositoryMock->method('findByUUID')->willReturn(null);
        $this->expectException(NotFoundException::class);
        $this->service->getUser('nonexistent-uuid');
    }

    public function testCreateUserThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);
        $this->service->createUser(['username' => '', 'password' => '']);
    }

    public function testCreateUserSuccess(): void
    {
        $userData = ['username' => 'user1', 'password' => 'secret'];
        $this->repositoryMock->method('createUser')->willReturn(1);

        $user = $this->service->createUser($userData);
        $this->assertIsArray($user);
        $this->assertEquals('user1', $user['username']);
    }

    public function testUpdateUserThrowsConflict(): void
    {
        $this->repositoryMock->method('findByUUID')->willReturn([
            'id' => 1, 'uuid' => 'uuid1', 'username' => 'user1', 'password' => 'hashed', 'is_active' => 1
        ]);
        $this->repositoryMock->method('updateUser')->willReturn(0);

        $this->expectException(ConflictException::class);
        $this->service->updateUserByUuid('uuid1', ['password' => 'newpass']);
    }

    public function testDeleteUserThrowsNotFound(): void
    {
        $this->repositoryMock->method('findByUUID')->willReturn(null);
        $this->expectException(NotFoundException::class);
        $this->service->deleteUserByUuid('uuid1');
    }
}
