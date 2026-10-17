<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Order;

use App\Domain\Customer\Entity\Customer;
use App\Domain\Customer\ValueObject\Email;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\DatabaseWebTestCase;

final class OrderControllerTest extends DatabaseWebTestCase
{
    private function authedToken(): string
    {
        $this->createAdmin('user@test.com');

        return $this->loginAs('user@test.com');
    }

    private function persistCustomer(): Customer
    {
        $customer = Customer::register('Ada Lovelace', new Email('ada@test.com'));
        $this->em->persist($customer);
        $this->em->flush();

        return $customer;
    }

    private function persistProduct(int $stock = 10): Product
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), $stock);
        $this->em->persist($product);
        $this->em->flush();

        return $product;
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/orders');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRequiresAdministratorRole(): void
    {
        $this->createUser('member@test.com');
        $token = $this->loginAs('member@test.com');
        $this->client->request('GET', '/api/orders', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(403);
    }

    public function testFullLifecycle(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();
        $product = $this->persistProduct();

        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => $product->getId(), 'quantity' => 2])
        );
        self::assertResponseStatusCodeSame(201);
        $order = $this->decodeResponse();
        self::assertSame('draft', $order['status']);
        self::assertCount(1, $order['lines']);

        $this->client->request('GET', '/api/orders', [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->decodeResponse()['data']);

        $this->client->request('GET', '/api/orders/'.$order['id'], [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame($order['id'], $this->decodeResponse()['id']);

        $this->client->request('POST', '/api/orders/'.$order['id'].'/place', [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame('placed', $this->decodeResponse()['status']);
    }

    public function testCancelDraftOrder(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();
        $product = $this->persistProduct();

        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => $product->getId(), 'quantity' => 1])
        );
        $order = $this->decodeResponse();

        $this->client->request('POST', '/api/orders/'.$order['id'].'/cancel', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertSame('cancelled', $this->decodeResponse()['status']);
    }

    public function testAddLineReturns404WhenProductMissing(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();

        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => 999999, 'quantity' => 1])
        );

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetByIdReturns404ForUnknownOrder(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/orders/999999', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(404);
    }

    public function testExportAllReturnsXlsFile(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/orders/export?all=true', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/vnd.ms-excel', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testExportSelectedIdsReturnsXlsFile(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();
        $product = $this->persistProduct();
        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => $product->getId(), 'quantity' => 1])
        );
        $order = $this->decodeResponse();

        $this->client->request('GET', '/api/orders/export?ids[]='.$order['id'], [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/vnd.ms-excel', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testExportWithoutSelectionOrAllReturns400(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/orders/export', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(400);
    }

    public function testUpdateLineQuantity(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();
        $product = $this->persistProduct();

        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => $product->getId(), 'quantity' => 1])
        );
        $order = $this->decodeResponse();
        $lineId = $order['lines'][0]['id'];

        $this->client->request(
            'PATCH',
            '/api/orders/'.$order['id'].'/lines/'.$lineId,
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['quantity' => 4])
        );

        self::assertResponseIsSuccessful();
        $updated = $this->decodeResponse();
        self::assertSame(4, $updated['lines'][0]['quantity']);
        self::assertSame(4000, $updated['totalAmountMinorUnits']);
    }

    public function testRemoveLine(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();
        $product = $this->persistProduct();

        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => $product->getId(), 'quantity' => 1])
        );
        $order = $this->decodeResponse();
        $lineId = $order['lines'][0]['id'];

        $this->client->request('DELETE', '/api/orders/'.$order['id'].'/lines/'.$lineId, [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->decodeResponse()['lines']);
    }

    public function testUpdateLineQuantityReturns404WhenLineNotFound(): void
    {
        $token = $this->authedToken();
        $customer = $this->persistCustomer();
        $product = $this->persistProduct();

        $this->client->request(
            'POST',
            '/api/orders/lines',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['customerId' => $customer->getId(), 'productId' => $product->getId(), 'quantity' => 1])
        );
        $order = $this->decodeResponse();

        $this->client->request(
            'PATCH',
            '/api/orders/'.$order['id'].'/lines/999999',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['quantity' => 4])
        );

        self::assertResponseStatusCodeSame(404);
    }
}
