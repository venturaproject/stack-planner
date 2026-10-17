<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Query;

use App\Application\Auth\Query\AuthReadModelInterface;
use App\Application\Auth\Query\ListUsersHandler;
use App\Application\Auth\Query\ListUsersQuery;
use App\Domain\Auth\Entity\User;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListUsersHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsPagedUsers(): void
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');
        self::setId($user, 'user-1');

        $users = $this->createStub(AuthReadModelInterface::class);
        $users->method('findUsersPaginated')->willReturn([$user]);
        $users->method('countUsers')->willReturn(1);

        $handler = new ListUsersHandler($users);

        $response = $handler->handle(new ListUsersQuery(1, 20));

        self::assertSame(1, $response->page);
        self::assertSame(20, $response->size);
        self::assertSame(1, $response->totalElements);
        self::assertSame(1, $response->totalPages);
        self::assertCount(1, $response->data);
        self::assertSame('me@example.com', $response->data[0]->email);
    }

    public function testComputesTotalPagesFromTotalAndSize(): void
    {
        $users = $this->createStub(AuthReadModelInterface::class);
        $users->method('findUsersPaginated')->willReturn([]);
        $users->method('countUsers')->willReturn(45);

        $handler = new ListUsersHandler($users);

        $response = $handler->handle(new ListUsersQuery(2, 20));

        self::assertSame(3, $response->totalPages);
    }
}
