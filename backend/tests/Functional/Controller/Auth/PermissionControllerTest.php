<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use App\Tests\Support\DatabaseWebTestCase;

final class PermissionControllerTest extends DatabaseWebTestCase
{
    private function adminToken(): string
    {
        $this->createAdmin('admin@test.com');

        return $this->loginAs('admin@test.com');
    }

    public function testRequiresAdminRole(): void
    {
        $this->createUser('plain@test.com');
        $token = $this->loginAs('plain@test.com');

        $this->client->request('GET', '/api/permissions', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(403);
    }

    public function testFullCrudLifecycle(): void
    {
        $token = $this->adminToken();

        $this->client->request(
            'POST',
            '/api/permissions',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'orders.view', 'description' => 'View orders'])
        );
        self::assertResponseStatusCodeSame(201);
        $created = $this->decodeResponse();
        self::assertSame('orders.view', $created['name']);

        $this->client->request('GET', '/api/permissions', [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->decodeResponse());

        $this->client->request(
            'PATCH',
            '/api/permissions/'.$created['id'],
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'orders.manage', 'description' => null])
        );
        self::assertResponseIsSuccessful();
        self::assertSame('orders.manage', $this->decodeResponse()['name']);

        $this->client->request('DELETE', '/api/permissions/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);
    }

    public function testCreateReturns409WhenNameAlreadyExists(): void
    {
        $token = $this->adminToken();
        $body = $this->jsonBody(['name' => 'duplicate.perm', 'description' => null]);

        $this->client->request('POST', '/api/permissions', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/permissions', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(409);
    }
}
