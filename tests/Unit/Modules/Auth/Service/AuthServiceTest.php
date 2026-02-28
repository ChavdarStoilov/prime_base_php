<?php

use App\Modules\Auth\Service\AuthService;
use App\Modules\Users\Domain\User;
use App\Modules\Users\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $userRepoMock;

    protected function setUp(): void
    {
        $this->userRepoMock = $this->createMock(UserRepository::class);
        $this->authService = new AuthService($this->userRepoMock);
    }

    public function testAuthenticateSuccess(): void
    {
        $password = 'secret';
        $hashed = password_hash($password, PASSWORD_ARGON2ID);

        $this->userRepoMock->method('findByUsername')->willReturn([
            'uuid' => 'uuid1', 'username' => 'user1', 'password' => $hashed
        ]);

        $user = $this->authService->authenticate('user1', $password);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user1', $user->getUsername());
    }

    public function testAuthenticateInvalidPassword(): void
    {
        $hashed = password_hash('secret', PASSWORD_ARGON2ID);

        $this->userRepoMock->method('findByUsername')->willReturn([
            'uuid' => 'uuid1', 'username' => 'user1', 'password' => $hashed
        ]);

        $user = $this->authService->authenticate('user1', 'wrongpass');
        $this->assertNull($user);
    }

    public function testAuthenticateUserNotFound(): void
    {
        $this->userRepoMock->method('findByUsername')->willReturn(null);

        $user = $this->authService->authenticate('unknown', 'any');
        $this->assertNull($user);
    }
}
