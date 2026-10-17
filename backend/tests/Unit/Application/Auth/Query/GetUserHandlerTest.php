<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Query;

use App\Application\Auth\Query\AuthReadModelInterface;
use App\Application\Auth\Query\GetUserHandler;
use App\Application\Auth\Query\GetUserQuery;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class GetUserHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsUserById(): void
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        $users = $this->createStub(AuthReadModelInterface::class);
        $users->method('findUserById')->willReturn($user);

        $handler = new GetUserHandler($users);

        $response = $handler->handle(new GetUserQuery('user-1'));

        self::assertSame('user-1', $response->id);
        self::assertSame('me@example.com', $response->email);
    }

    public function testThrowsWhenUserNotFound(): void
    {
        $users = $this->createStub(AuthReadModelInterface::class);
        $users->method('findUserById')->willReturn(null);

        $handler = new GetUserHandler($users);

        $this->expectException(UserNotFoundException::class);

        $handler->handle(new GetUserQuery('missing'));
    }
}
