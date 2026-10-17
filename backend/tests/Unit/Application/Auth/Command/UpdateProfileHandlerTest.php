<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Command;

use App\Application\Auth\Command\UpdateProfileCommand;
use App\Application\Auth\Command\UpdateProfileHandler;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdateProfileHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function existingUser(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Old');
        $user->setLastName('Name');
        self::setId($user, 'user-1');

        return $user;
    }

    public function testUpdatesNameAndLanguage(): void
    {
        $user = $this->existingUser();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->method('save')->willReturnArgument(0);

        $cmd = new UpdateProfileCommand();
        $cmd->userId = 'user-1';
        $cmd->firstName = 'New';
        $cmd->lastName = 'Name';
        $cmd->preferredLanguage = 'en';

        $handler = new UpdateProfileHandler($users);
        $response = $handler->handle($cmd);

        self::assertSame('New', $response->firstName);
        self::assertSame('en', $response->preferredLanguage);
    }

    public function testKeepsLanguageWhenNotProvided(): void
    {
        $user = $this->existingUser();
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn($user);
        $users->method('save')->willReturnArgument(0);

        $cmd = new UpdateProfileCommand();
        $cmd->userId = 'user-1';
        $cmd->firstName = 'New';
        $cmd->lastName = 'Name';

        $handler = new UpdateProfileHandler($users);
        $response = $handler->handle($cmd);

        self::assertSame('es', $response->preferredLanguage);
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $users = $this->createStub(UserRepositoryInterface::class);
        $users->method('findById')->willReturn(null);

        $cmd = new UpdateProfileCommand();
        $cmd->userId = 'missing';
        $cmd->firstName = 'New';
        $cmd->lastName = 'Name';

        $handler = new UpdateProfileHandler($users);

        $this->expectException(UserNotFoundException::class);

        $handler->handle($cmd);
    }
}
