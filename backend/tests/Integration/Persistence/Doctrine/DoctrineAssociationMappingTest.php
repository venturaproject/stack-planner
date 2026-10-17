<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence\Doctrine;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use App\Domain\Order\Entity\Order;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\DatabaseKernelTestCase;

final class DoctrineAssociationMappingTest extends DatabaseKernelTestCase
{
    public function testManyToManyAssociationsPersistAndHydrateThroughIterableProperties(): void
    {
        $permission = new Permission();
        $permission->setName('orders:view');

        $role = new Role();
        $role->setName('editor');
        $role->setPermissions([$permission]);

        $user = new User();
        $user->setEmail('editor@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Editor');
        $user->setLastName('User');
        $user->setRoles([$role]);
        $user->setDirectPermissions([$permission]);

        $this->em->persist($permission);
        $this->em->persist($role);
        $this->em->persist($user);
        $this->em->flush();
        $userId = $user->getId();

        $this->em->clear();
        $reloaded = $this->em->find(User::class, $userId);

        self::assertInstanceOf(User::class, $reloaded);
        self::assertCount(1, $reloaded->getRoleEntities());
        self::assertCount(1, $reloaded->getDirectPermissions());
        self::assertCount(1, [...$reloaded->getRoleEntities()][0]->getPermissions());
    }

    public function testOneToManyAssociationPersistsAndRemovesLinesThroughIterableProperty(): void
    {
        $order = Order::startDraft(1, null);
        $order->addLine(1, new Sku('TSH-0001'), 'T-Shirt', Money::fromMinorUnits(1000, Currency::USD), 1);

        $this->em->persist($order);
        $this->em->flush();
        $orderId = $order->getId();

        $this->em->clear();
        $reloaded = $this->em->find(Order::class, $orderId);

        self::assertInstanceOf(Order::class, $reloaded);
        self::assertCount(1, $reloaded->getLines());

        $reloaded->removeLine($reloaded->getLines()[0]->getId());
        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->find(Order::class, $orderId);

        self::assertInstanceOf(Order::class, $reloaded);
        self::assertSame([], $reloaded->getLines());
    }
}
