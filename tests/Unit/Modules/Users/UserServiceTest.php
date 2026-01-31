<?php

use PHPUnit\Framework\TestCase;
use App\Modules\Users\Service\UserService;
use App\Modules\Users\Repository\UserRepository;
use App\Modules\Users\Controller\Domain\User;

final class UserServiceTest extends TestCase
{
    private UserRepository $repository;
    private UserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepository::class);
        $this->service = new UserService($this->repository);
    }

    public function testUpdateUserByUuidSuccess(): void
    {
        $user = new User(1, 'uuid-123', 'test');
        $user->setIsActive(true);

        $this->repository
            ->method('findByUuid')
            ->willReturn($user);

        $this->repository
            ->method('updateUser')
            ->willReturn(1);

        $result = $this->service->updateUserByUuid('3d63a377-5b90-4ca2-b214-e9c8d6c5bade', [
            'uuid' => 'uuid-123',
            'is_active' => 1
        ]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame(1, $result->isActive());
    }

    public function testUpdateUserByUuidFailsIfUserNotFound(): void
    {
        $this->repository
            ->method('findByUuid')
            ->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('User not found');

        $this->service->updateUserByUuid([
            'uuid' => 'missing-uuid'
        ]);
    }

    public function testUpdateFailsWhenNoRowsAffected(): void
    {
        $user = new User(1, 'uuid', 'test');

        $this->repository
            ->method('findByUuid')
            ->willReturn($user);

        $this->repository
            ->method('updateUser')
            ->willReturn(0);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Update failed');

        $this->service->updateUserByUuid([
            'uuid' => 'uuid'
        ]);
    }

    public function testUsernameCannotBeUpdated(): void
    {
        $user = new User(1, 'uuid', 'test');

        $this->repository
            ->method('findByUuid')
            ->willReturn($user);

        echo $user;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot update username');

        $this->service->updateUserByUuid([
            'uuid' => 'uuid',
            'username' => 'hacker'
        ]);
    }
}
