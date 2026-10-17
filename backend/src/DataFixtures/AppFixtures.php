<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\Auth\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $this->loadUsers($manager);
        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@test.com');
        $user->setFirstName('Admin');
        $user->setLastName('Test');
        // bcrypt hash of "password"
        $user->setPasswordHash('$2y$10$VG2HL.7OyML2O9R/VIk2gOBTZNy8Ydon6as4wR2A82IAMVDgnw/7a');
        $manager->persist($user);
        $this->addReference('user-admin', $user);
    }
}
