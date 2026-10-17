<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Auth\Entity;

use App\Domain\Auth\Entity\Permission;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class PermissionTest extends TestCase
{
    public function testSettersUpdateFields(): void
    {
        $permission = new Permission();

        $permission->setName('orders.view');
        $permission->setDescription('View orders');

        self::assertSame('orders.view', $permission->getName());
        self::assertSame('View orders', $permission->getDescription());
        self::assertNull($permission->getId());
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        self::assertInstanceOf(DateTimeImmutable::class, (new Permission())->getCreatedAt());
    }
}
