<?php

declare(strict_types=1);

namespace App\Tests\Integration\DataFixtures;

use App\DataFixtures\AppFixtures;
use App\Domain\Auth\Entity\User;
use App\Tests\Support\DatabaseKernelTestCase;
use Doctrine\Common\DataFixtures\ReferenceRepository;

final class AppFixturesTest extends DatabaseKernelTestCase
{
    public function testLoadPersistsAdminUserAndRegistersReference(): void
    {
        $fixtures = new AppFixtures();
        $fixtures->setReferenceRepository(new ReferenceRepository($this->em));

        $fixtures->load($this->em);

        $user = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@test.com']);
        self::assertNotNull($user);
        self::assertSame('Admin', $user->getFirstName());
        self::assertSame($user, $fixtures->getReference('user-admin', User::class));
    }
}
