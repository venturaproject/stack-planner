<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Product;

use App\Tests\Support\DatabaseWebTestCase;
use App\Tests\Support\TestImage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProductControllerTest extends DatabaseWebTestCase
{
    private function authedToken(): string
    {
        $this->createAdmin('user@test.com');

        return $this->loginAs('user@test.com');
    }

    public function testRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/products');

        self::assertResponseStatusCodeSame(401);
    }

    public function testRequiresAdministratorRole(): void
    {
        $this->createUser('member@test.com');
        $token = $this->loginAs('member@test.com');
        $this->client->request('GET', '/api/products', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(403);
    }

    public function testFullCrudLifecycle(): void
    {
        $token = $this->authedToken();

        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody([
                'sku' => 'TSH-0001',
                'name' => 'T-Shirt',
                'description' => 'A shirt',
                'priceAmountMinorUnits' => 1999,
                'priceCurrency' => 'USD',
                'initialStock' => 10,
            ])
        );
        self::assertResponseStatusCodeSame(201);
        $created = $this->decodeResponse();
        self::assertSame('TSH-0001', $created['sku']);

        $this->client->request('GET', '/api/products', [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertNotEmpty($this->decodeResponse()['data']);

        $this->client->request('GET', '/api/products/'.$created['id'], [], [], $this->authHeaders($token));
        self::assertResponseIsSuccessful();
        self::assertSame('T-Shirt', $this->decodeResponse()['name']);

        $this->client->request(
            'PATCH',
            '/api/products/'.$created['id'],
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['name' => 'Premium T-Shirt'])
        );
        self::assertResponseIsSuccessful();
        self::assertSame('Premium T-Shirt', $this->decodeResponse()['name']);

        $this->client->request(
            'POST',
            '/api/products/'.$created['id'].'/stock-adjustments',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['delta' => 5, 'reason' => 'restock'])
        );
        self::assertResponseIsSuccessful();
        self::assertSame(15, $this->decodeResponse()['stockQuantity']);
    }

    public function testCreateReturns409WhenSkuAlreadyExists(): void
    {
        $token = $this->authedToken();
        $body = $this->jsonBody(['sku' => 'DUP-0001', 'name' => 'Duplicate', 'initialStock' => 1]);

        $this->client->request('POST', '/api/products', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(201);

        $this->client->request('POST', '/api/products', [], [], $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'], $body);
        self::assertResponseStatusCodeSame(409);
    }

    public function testGetByIdReturns404ForUnknownProduct(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/products/999999', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(404);
    }

    public function testExportRequiresSelectionWhenNotExportingAll(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/products/export', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(400);
    }

    public function testExportAllReturnsXlsFile(): void
    {
        $token = $this->authedToken();

        $this->client->request('GET', '/api/products/export?all=true', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/vnd.ms-excel', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testExportSelectedIdsReturnsXlsFile(): void
    {
        $token = $this->authedToken();
        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['sku' => 'EXP-0001', 'name' => 'Exportable', 'initialStock' => 1])
        );
        $created = $this->decodeResponse();

        $this->client->request('GET', '/api/products/export?ids[]='.$created['id'], [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/vnd.ms-excel', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    // ── Product image ────────────────────────────────────────────────────────

    private function createProduct(string $token, string $sku): array
    {
        $this->client->request(
            'POST',
            '/api/products',
            [],
            [],
            $this->authHeaders($token) + ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['sku' => $sku, 'name' => 'Product', 'initialStock' => 1])
        );

        return $this->decodeResponse();
    }

    public function testUploadImageStoresFileAndSetsImageFilename(): void
    {
        $token = $this->authedToken();
        $product = $this->createProduct($token, 'IMG-0001');

        $path = sys_get_temp_dir().'/product-image-'.uniqid().'.png';
        file_put_contents($path, TestImage::onePixelPng());
        $file = new UploadedFile($path, 'photo.png', 'image/png', null, true);

        $this->client->request(
            'POST',
            '/api/products/'.$product['id'].'/image',
            [],
            ['image' => $file],
            $this->authHeaders($token)
        );

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertSame($product['id'].'.png', $data['imageFilename']);

        @unlink($path);
    }

    public function testUploadImageReturns400WhenNoFileGiven(): void
    {
        $token = $this->authedToken();
        $product = $this->createProduct($token, 'IMG-0002');

        $this->client->request(
            'POST',
            '/api/products/'.$product['id'].'/image',
            [],
            [],
            $this->authHeaders($token)
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testGetImageReturns404WhenProductHasNoImage(): void
    {
        $token = $this->authedToken();
        $product = $this->createProduct($token, 'IMG-0003');

        $this->client->request('GET', '/api/products/'.$product['id'].'/image', [], [], $this->authHeaders($token));

        self::assertResponseStatusCodeSame(404);
    }

    public function testGetImageStreamsUploadedFile(): void
    {
        $token = $this->authedToken();
        $product = $this->createProduct($token, 'IMG-0004');

        $path = sys_get_temp_dir().'/product-image-'.uniqid().'.png';
        file_put_contents($path, TestImage::onePixelPng());
        $file = new UploadedFile($path, 'photo.png', 'image/png', null, true);
        $this->client->request('POST', '/api/products/'.$product['id'].'/image', [], ['image' => $file], $this->authHeaders($token));
        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/products/'.$product['id'].'/image', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertSame('image/png', $this->client->getResponse()->headers->get('Content-Type'));
        ob_start();
        $this->client->getResponse()->sendContent();
        self::assertSame(TestImage::onePixelPng(), ob_get_clean());

        @unlink($path);
    }

    public function testRemoveImageClearsImageFilename(): void
    {
        $token = $this->authedToken();
        $product = $this->createProduct($token, 'IMG-0005');

        $path = sys_get_temp_dir().'/product-image-'.uniqid().'.png';
        file_put_contents($path, TestImage::onePixelPng());
        $file = new UploadedFile($path, 'photo.png', 'image/png', null, true);
        $this->client->request('POST', '/api/products/'.$product['id'].'/image', [], ['image' => $file], $this->authHeaders($token));
        self::assertResponseIsSuccessful();

        $this->client->request('DELETE', '/api/products/'.$product['id'].'/image', [], [], $this->authHeaders($token));

        self::assertResponseIsSuccessful();
        self::assertNull($this->decodeResponse()['imageFilename']);

        @unlink($path);
    }
}
