<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Boots a browser client for functional tests that hit real HTTP routes + the
 * real database. Test isolation (wrapping each test in a transaction that's
 * rolled back afterwards, even across the EntityManager resets that Doctrine
 * triggers on certain exceptions) is handled globally by
 * DAMA\DoctrineTestBundle at the DBAL connection level — see phpunit.xml.dist.
 */
abstract class DatabaseWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @param string[] $permissionNames
     * @param string[] $roleNames
     */
    protected function createUser(string $email = 'user@test.com', string $password = 'password', array $permissionNames = [], array $roleNames = []): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPasswordHash(password_hash($password, \PASSWORD_BCRYPT));

        if ([] !== $permissionNames) {
            $permissions = [];
            foreach ($permissionNames as $name) {
                $permission = new Permission();
                $permission->setName($name);
                $this->em->persist($permission);
                $permissions[] = $permission;
            }
            $user->setDirectPermissions($permissions);
        }

        if ([] !== $roleNames) {
            $roles = [];
            foreach ($roleNames as $name) {
                $role = new Role();
                $role->setName($name);
                $this->em->persist($role);
                $roles[] = $role;
            }
            $user->setRoles($roles);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createAdmin(string $email = 'admin@test.com', string $password = 'password'): User
    {
        return $this->createUser($email, $password, roleNames: ['ADMIN']);
    }

    protected function loginAs(string $email, string $password = 'password'): string
    {
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => $email, 'password' => $password]) ?: ''
        );

        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ('access_token' === $cookie->getName()) {
                return $cookie->getValue();
            }
        }

        self::fail('Login did not set an access_token cookie.');
    }

    protected function authHeaders(string $token): array
    {
        return ['HTTP_AUTHORIZATION' => 'Bearer '.$token];
    }

    protected function jsonBody(array $data): string
    {
        return json_encode($data, \JSON_THROW_ON_ERROR);
    }

    protected function decodeResponse(): array
    {
        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }
}
