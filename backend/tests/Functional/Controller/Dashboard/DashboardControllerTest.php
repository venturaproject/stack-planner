<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Dashboard;

use App\Tests\Support\DatabaseWebTestCase;

final class DashboardControllerTest extends DatabaseWebTestCase
{
    public function testReturnsAggregatedCountsForAuthenticatedUser(): void
    {
        $this->createUser('dash@test.com');
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
}
