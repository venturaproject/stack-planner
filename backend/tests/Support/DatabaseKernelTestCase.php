<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Boots the kernel for integration tests that hit the real database. Test
 * isolation (wrapping each test in a transaction that's rolled back
 * afterwards, even across the EntityManager resets that Doctrine triggers on
 * certain exceptions) is handled globally by DAMA\DoctrineTestBundle at the
 * DBAL connection level — see phpunit.xml.dist.
 */
abstract class DatabaseKernelTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }
}
