<?php

use App\Modules\Users\Domain\User;
use PHPUnit\Framework\TestCase;

final class UserEntityTest extends TestCase
{
    public function testSetIsActiveCastsToInt(): void
    {
        $user = new User(1, 'uuid', 'test');

        $user->setIsActive(true);
        $this->assertSame(1, $user->isActive());

        $user->setIsActive(false);
        $this->assertSame(0, $user->isActive());
    }

    public function testToPublicArrayDoesNotExposePassword(): void
    {
        $user = new User(1, 'uuid', 'test');
        $user->setPassword('hashed_password');

        $data = $user->toPublicArray();

        $this->assertArrayNotHasKey('password', $data);
    }
}
