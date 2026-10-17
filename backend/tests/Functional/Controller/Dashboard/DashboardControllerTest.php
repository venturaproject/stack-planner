<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Dashboard;

use App\Tests\Support\DatabaseWebTestCase;

final class DashboardControllerTest extends DatabaseWebTestCase
{
    public function testReturnsAggregatedCountsForAdministrator(): void
    {
        $this->createAdmin('dash@test.com');
        $token = $this->loginAs('dash@test.com');

        $this->client->request('GET', '/api/dashboard/', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertArrayHasKey('totalProducts', $data);
        self::assertArrayHasKey('totalOrders', $data);
        self::assertArrayHasKey('totalCustomers', $data);
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/dashboard/');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRequiresAdministratorRole(): void
    {
        $this->createUser('user@test.com');
        $token = $this->loginAs('user@test.com');

        $this->client->request('GET', '/api/dashboard/', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(403);
    }
}
