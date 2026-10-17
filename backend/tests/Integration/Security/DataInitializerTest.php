<?php

declare(strict_types=1);

namespace App\Tests\Integration\Security;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Infrastructure\Security\DataInitializer;
use App\Infrastructure\Security\Symfony\SecurityUser;
use App\Tests\Support\DatabaseKernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DataInitializerTest extends DatabaseKernelTestCase
{
    private function tester(): CommandTester
    {
        return new CommandTester(self::getContainer()->get(DataInitializer::class));
    }

    public function testSeedsRolesPermissionsAdminAndPlannerDataWithoutCommercialCatalog(): void
    {
        $exitCode = $this->tester()->execute([]);

        self::assertSame(0, $exitCode);

        self::assertNotNull($this->em->getRepository(Role::class)->findOneBy(['name' => 'ADMIN']));
        self::assertNotNull($this->em->getRepository(Role::class)->findOneBy(['name' => 'USER']));
        self::assertCount(5, $this->em->getRepository(Permission::class)->findAll());

        $admin = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@admin.com']);
        self::assertNotNull($admin);
        self::assertContains('ROLE_ADMIN', (new SecurityUser($admin))->getRoles());

        $orders = self::getContainer()->get(OrderRepositoryInterface::class);
        self::assertSame(0, $orders->countAll(null));
    }

    public function testRunningTwiceWithoutForceDoesNotDuplicateData(): void
    {
        $this->tester()->execute([]);
        $this->tester()->execute([]);

        self::assertCount(5, $this->em->getRepository(Permission::class)->findAll());
        self::assertCount(2, $this->em->getRepository(Role::class)->findAll());
        self::assertCount(1, $this->em->getRepository(User::class)->findBy(['email' => 'admin@admin.com']));

        $orders = self::getContainer()->get(OrderRepositoryInterface::class);
        self::assertSame(0, $orders->countAll(null));
    }

    public function testRunningWithForceOnExistingDataSkipsAdminRecreation(): void
    {
        $this->tester()->execute([]);

        $exitCode = $this->tester()->execute(['--force' => true]);

        self::assertSame(0, $exitCode);
        self::assertCount(1, $this->em->getRepository(User::class)->findBy(['email' => 'admin@admin.com']));
    }
}
