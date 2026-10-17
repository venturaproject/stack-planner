<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use App\Domain\Auth\Entity\Permission;
use App\Tests\Support\DatabaseWebTestCase;

final class RoleControllerTest extends DatabaseWebTestCase
{
    private function adminToken(): string
    {
        $this->createAdmin('admin@test.com');

        return $this->loginAs('admin@test.com');
    }

    private function createPermission(string $name): string
    {
        $permission = new Permission();
        $permission->setName($name);
        $this->em->persist($permission);
        $this->em->flush();

        return $permission->requireId();
    }

    /** @return string[] */
    private function outboxEventIdsFor(string $eventClass): array
    {
        return $this->em->getConnection()->fetchFirstColumn(
            'SELECT id FROM outbox_events WHERE event_type = :type',
            ['type' => $eventClass]
        );
    }

    public function testRequiresAdminRole(): void
    {
        $this->createUser('plain@test.com');
        $token = $this->loginAs('plain@test.com');

        $this->client->request('GET', '/api/roles', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(403);
    }

    public function testFullCrudLifecycle(): void
    {
        $token = $this->adminToken();

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'editor', 'description' => 'Can edit content'])
        );
        self::assertResponseStatusCodeSame(201);
        $created = $this->decodeResponse();
        self::assertSame('editor', $created['name']);

        $this->client->request('GET', '/api/roles', [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->decodeResponse());

        $this->client->request('GET', '/api/roles/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame('editor', $this->decodeResponse()['name']);

        $this->client->request(
            'PATCH',
            '/api/roles/'.$created['id'],
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'senior-editor', 'description' => null])
        );
        self::assertResponseIsSuccessful();
        self::assertSame('senior-editor', $this->decodeResponse()['name']);

        $this->client->request('DELETE', '/api/roles/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);

        $this->client->request('GET', '/api/roles/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertResponseStatusCodeSame(404);
    }

    public function testCreateAppliesGivenPermissions(): void
    {
        $token = $this->adminToken();
        $permissionId = $this->createPermission('roles.manage');

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'manager', 'description' => null, 'permissions' => [$permissionId]])
        );
        self::assertResponseStatusCodeSame(201);
        $created = $this->decodeResponse();
        self::assertSame(['roles.manage'], array_column($created['permissions'], 'name'));

        // Fetching it back confirms the join table was actually persisted, not just
        // reflected in the response DTO from the in-memory entity.
        $this->client->request('GET', '/api/roles/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertSame(['roles.manage'], array_column($this->decodeResponse()['permissions'], 'name'));

        self::assertNotEmpty($this->outboxEventIdsFor('App\Domain\Auth\Event\RoleCreated'));
        self::assertNotEmpty($this->outboxEventIdsFor('App\Domain\Auth\Event\RolePermissionsChanged'));
    }

    public function testUpdateAppliesChangedPermissionsEvenWithoutOtherFieldChanges(): void
    {
        $token = $this->adminToken();
        $permissionId = $this->createPermission('reports.view');

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'analyst', 'description' => null])
        );
        self::assertResponseStatusCodeSame(201);
        $created = $this->decodeResponse();
        self::assertSame([], $created['permissions']);

        // Same name/description as creation — only the permission set differs. This is
        // the scenario that risks Doctrine's postUpdate never firing (a ManyToMany
        // collection change alone doesn't produce a scalar changeset), which would
        // silently drop the RolePermissionsChanged event before it reaches the outbox.
        $this->client->request(
            'PATCH',
            '/api/roles/'.$created['id'],
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'analyst', 'description' => null, 'permissions' => [$permissionId]])
        );
        self::assertResponseIsSuccessful();
        self::assertSame(['reports.view'], array_column($this->decodeResponse()['permissions'], 'name'));

        $this->client->request('GET', '/api/roles/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertSame(['reports.view'], array_column($this->decodeResponse()['permissions'], 'name'));

        self::assertNotEmpty($this->outboxEventIdsFor('App\Domain\Auth\Event\RolePermissionsChanged'));
    }

    public function testUpdateWithUnchangedPermissionsDoesNotWriteAnExtraOutboxEvent(): void
    {
        $token = $this->adminToken();
        $permissionId = $this->createPermission('billing.view');

        $this->client->request(
            'POST',
            '/api/roles',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'billing-clerk', 'description' => null, 'permissions' => [$permissionId]])
        );
        $created = $this->decodeResponse();
        $countAfterCreate = \count($this->outboxEventIdsFor('App\Domain\Auth\Event\RolePermissionsChanged'));

        // Re-sends the exact same permission set on an unrelated rename.
        $this->client->request(
            'PATCH',
            '/api/roles/'.$created['id'],
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'billing-clerk-renamed', 'description' => null, 'permissions' => [$permissionId]])
        );
        self::assertResponseIsSuccessful();

        self::assertCount($countAfterCreate, $this->outboxEventIdsFor('App\Domain\Auth\Event\RolePermissionsChanged'));
    }

    public function testCreateReturns409WhenNameAlreadyExists(): void
    {
        $token = $this->adminToken();
        $body = $this->jsonBody(['name' => 'duplicate', 'description' => null]);

        $this->client->request('POST', '/api/roles', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/roles', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(409);
    }
}
