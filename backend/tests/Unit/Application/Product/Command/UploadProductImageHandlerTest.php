<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Command;

use App\Application\Product\Command\UploadProductImageCommand;
use App\Application\Product\Command\UploadProductImageHandler;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Infrastructure\Storage\LocalFileStorage;
use App\Tests\Support\EntityIdSetter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UploadProductImageHandlerTest extends TestCase
{
    use EntityIdSetter;

    private string $mediaDir;

    protected function setUp(): void
    {
        $this->mediaDir = sys_get_temp_dir().'/product-image-test-'.uniqid();
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

    public function testMovesFileAndSetsImageFilename(): void
    {
        $product = $this->existingProduct();
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        self::assertNotFalse($tmpFile);

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $handler = new UploadProductImageHandler($products, new LocalFileStorage($this->mediaDir));

        $response = $handler->handle(new UploadProductImageCommand(1, $tmpFile, 'PNG'));

        self::assertSame('1.png', $response->imageFilename);
        self::assertFileExists($this->mediaDir.'/products/1.png');
    }

    public function testReplacesPreviousImageWithDifferentExtension(): void
    {
        $product = $this->existingProduct();
        $product->attachImage('1.jpg');

        $dir = $this->mediaDir.'/products';
        mkdir($dir, 0755, true);
        file_put_contents($dir.'/1.jpg', 'old');

        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        self::assertNotFalse($tmpFile);

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $handler = new UploadProductImageHandler($products, new LocalFileStorage($this->mediaDir));
        $response = $handler->handle(new UploadProductImageCommand(1, $tmpFile, 'png'));

        self::assertSame('1.png', $response->imageFilename);
        self::assertFileExists($dir.'/1.png');
        self::assertFileDoesNotExist($dir.'/1.jpg');
    }

    public function testRejectsDisallowedExtension(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);

        $handler = new UploadProductImageHandler($products, new LocalFileStorage($this->mediaDir));

        $this->expectException(InvalidArgumentException::class);

        $handler->handle(new UploadProductImageCommand(1, '/tmp/whatever', 'exe'));
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        self::assertNotFalse($tmpFile);

        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn(null);

        $handler = new UploadProductImageHandler($products, new LocalFileStorage($this->mediaDir));

        $this->expectException(ProductNotFoundException::class);

        try {
            $handler->handle(new UploadProductImageCommand(999, $tmpFile, 'png'));
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }
}
