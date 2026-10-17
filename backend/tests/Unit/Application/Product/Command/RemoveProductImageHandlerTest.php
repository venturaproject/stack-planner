<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Command;

use App\Application\Product\Command\RemoveProductImageCommand;
use App\Application\Product\Command\RemoveProductImageHandler;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Infrastructure\Storage\LocalFileStorage;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class RemoveProductImageHandlerTest extends TestCase
{
    use EntityIdSetter;

    private string $mediaDir;

    protected function setUp(): void
    {
        $this->mediaDir = sys_get_temp_dir().'/product-image-test-'.uniqid();
        mkdir($this->mediaDir.'/products', 0755, true);
    }

    protected function tearDown(): void
    {
        $products = $this->mediaDir.'/products';
        if (is_dir($products)) {
            foreach (glob($products.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($products);
        }
        if (is_dir($this->mediaDir)) {
            rmdir($this->mediaDir);
        }
    }

    private function existingProduct(): Product
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), 5);
        self::setId($product, 1);

        return $product;
    }

    public function testDeletesFileAndClearsImageFilename(): void
    {
        $product = $this->existingProduct();
        $product->attachImage('1.png');
        file_put_contents($this->mediaDir.'/products/1.png', 'bytes');

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $handler = new RemoveProductImageHandler($products, new LocalFileStorage($this->mediaDir));
        $response = $handler->handle(new RemoveProductImageCommand(1));

        self::assertNull($response->imageFilename);
        self::assertFileDoesNotExist($this->mediaDir.'/products/1.png');
    }

    public function testIsNoOpWhenProductHasNoImage(): void
    {
        $product = $this->existingProduct();

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $handler = new RemoveProductImageHandler($products, new LocalFileStorage($this->mediaDir));
        $response = $handler->handle(new RemoveProductImageCommand(1));

        self::assertNull($response->imageFilename);
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn(null);

        $handler = new RemoveProductImageHandler($products, new LocalFileStorage($this->mediaDir));

        $this->expectException(ProductNotFoundException::class);

        $handler->handle(new RemoveProductImageCommand(999));
    }
}
