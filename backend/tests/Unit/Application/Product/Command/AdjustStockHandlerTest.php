<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Command;

use App\Application\Product\Command\AdjustStockCommand;
use App\Application\Product\Command\AdjustStockHandler;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\InsufficientStockException;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AdjustStockHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function existingProduct(int $stock = 10): Product
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), $stock);
        self::setId($product, 1);

        return $product;
    }

    public function testRestocksOnPositiveDelta(): void
    {
        $product = $this->existingProduct(10);
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $cmd = new AdjustStockCommand(delta: 5, reason: 'restock');
        $cmd->id = 1;

        $handler = new AdjustStockHandler($products);
        $response = $handler->handle($cmd);

        self::assertSame(15, $response->stockQuantity);
    }

    public function testReservesStockOnNegativeDelta(): void
    {
        $product = $this->existingProduct(10);
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $cmd = new AdjustStockCommand(delta: -3, reason: 'damaged');
        $cmd->id = 1;

        $handler = new AdjustStockHandler($products);
        $response = $handler->handle($cmd);

        self::assertSame(7, $response->stockQuantity);
    }

    public function testThrowsWhenDeltaIsZero(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);

        $cmd = new AdjustStockCommand(delta: 0);
        $cmd->id = 1;

        $handler = new AdjustStockHandler($products);

        $this->expectException(InvalidArgumentException::class);

        $handler->handle($cmd);
    }

    public function testThrowsWhenReservingMoreThanAvailable(): void
    {
        $product = $this->existingProduct(2);
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);

        $cmd = new AdjustStockCommand(delta: -5);
        $cmd->id = 1;

        $handler = new AdjustStockHandler($products);

        $this->expectException(InsufficientStockException::class);

        $handler->handle($cmd);
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn(null);

        $cmd = new AdjustStockCommand(delta: 1);
        $cmd->id = 999;

        $handler = new AdjustStockHandler($products);

        $this->expectException(ProductNotFoundException::class);

        $handler->handle($cmd);
    }
}
