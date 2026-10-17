<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Auth;

use App\Tests\Support\DatabaseWebTestCase;

final class UserControllerTest extends DatabaseWebTestCase
{
    private function adminToken(): string
    {
        $this->createAdmin('admin@test.com');

        return $this->loginAs('admin@test.com');
    }

    public function testListRequiresAdminRole(): void
    {
        $this->createUser('plain@test.com');
        $token = $this->loginAs('plain@test.com');

        $this->client->request('GET', '/api/users', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(403);
    }

    public function testAdminCanListUsers(): void
    {
        $token = $this->adminToken();

        $this->client->request('GET', '/api/users', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertArrayHasKey('data', $data);
    }

    public function testAdminCanCreateUser(): void
    {
        $token = $this->adminToken();

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'new@test.com', 'firstName' => 'Ada', 'lastName' => 'Lovelace'])
        );

        self::assertResponseStatusCodeSame(201);
        $data = $this->decodeResponse();
        self::assertSame('new@test.com', $data['email']);
    }

    public function testCreateReturns422OnValidationFailure(): void
    {
        $token = $this->adminToken();

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'not-an-email', 'firstName' => '', 'lastName' => 'Lovelace'])
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testAdminCanGetUpdateAndDeleteUser(): void
    {
        $token = $this->adminToken();
        $target = $this->createUser('target@test.com');

        $this->client->request('GET', '/api/users/'.$target->getId(), [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame('target@test.com', $this->decodeResponse()['email']);

        $this->client->request(
            'PATCH',
            '/api/users/'.$target->getId(),
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['firstName' => 'Updated', 'lastName' => 'Name'])
        );
        self::assertResponseIsSuccessful();
        self::assertSame('Updated', $this->decodeResponse()['firstName']);

        $this->client->request('DELETE', '/api/users/'.$target->getId(), [], [], $this->authHeaders($token));
        self::assertResponseStatusCodeSame(204);
    }

    public function testUpdateReturns422OnValidationFailure(): void
    {
        $token = $this->adminToken();
        $target = $this->createUser('blank-update@test.com');

        $this->client->request(
            'PATCH',
            '/api/users/'.$target->getId(),
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody([])
        );

        self::assertResponseStatusCodeSame(422);
    }

    public function testGetByIdReturns404ForUnknownUser(): void
    {
        $token = $this->adminToken();

        $this->client->request('GET', '/api/users/00000000-0000-0000-0000-000000000000', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(404);
    }

    public function testAuditLogEndpointReturnsPagedResult(): void
    {
        $token = $this->adminToken();
        $target = $this->createUser('audited@test.com');
        // Login is asynchronous: no audit side effect is visible before the worker runs.
        $this->loginAs('audited@test.com');

        $this->client->request('GET', '/api/users/'.$target->getId().'/audit-log', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertArrayHasKey('total', $data);
        self::assertSame(0, $data['total']);
        self::assertSame([], $data['data']);
    }
}
