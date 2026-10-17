<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Customer;

use App\Tests\Support\DatabaseWebTestCase;

final class CustomerControllerTest extends DatabaseWebTestCase
{
    private function authedToken(): string
    {
        $this->createUser('user@test.com');

        return $this->loginAs('user@test.com');
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/customers');

        self::assertResponseStatusCodeSame(401);
    }

    public function testFullLifecycle(): void
    {
        $token = $this->authedToken();

        $this->client->request(
            'POST',
            '/api/customers',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'Ada Lovelace', 'email' => 'ada@test.com'])
        );
        self::assertResponseStatusCodeSame(201);
        $created = $this->decodeResponse();
        self::assertSame('ada@test.com', $created['email']);

        $this->client->request('GET', '/api/customers', [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->decodeResponse()['data']);

        $this->client->request('GET', '/api/customers/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame('Ada Lovelace', $this->decodeResponse()['name']);

        $this->client->request(
            'PATCH',
            '/api/customers/'.$created['id'].'/address',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['street' => '221B Baker St', 'city' => 'London', 'postalCode' => 'NW1', 'country' => 'UK'])
        );
        self::assertResponseIsSuccessful();
        self::assertSame('221B Baker St', $this->decodeResponse()['addressStreet']);
    }

    public function testCreateReturns409WhenEmailAlreadyRegistered(): void
    {
        $token = $this->authedToken();
        $body = $this->jsonBody(['name' => 'Ada', 'email' => 'dup@test.com']);

        $this->client->request('POST', '/api/customers', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/customers', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(409);
    }

    public function testGetByIdReturns404ForUnknownCustomer(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/customers/999999', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(404);
    }

    public function testExportAllReturnsXlsFile(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/customers/export?all=true', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/vnd.ms-excel', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testExportSelectedIdsReturnsXlsFile(): void
    {
        $token = $this->authedToken();
        $this->client->request(
            'POST',
            '/api/customers',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'Ada Lovelace', 'email' => 'export@test.com'])
        );
        $created = $this->decodeResponse();

        $this->client->request('GET', '/api/customers/export?ids[]='.$created['id'], [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/vnd.ms-excel', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testExportWithoutSelectionOrAllReturns400(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/customers/export', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(400);
    }
}
