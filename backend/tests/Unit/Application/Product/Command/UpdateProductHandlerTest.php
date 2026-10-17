<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Command;

use App\Application\Product\Command\UpdateProductCommand;
use App\Application\Product\Command\UpdateProductHandler;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class UpdateProductHandlerTest extends TestCase
{
    use EntityIdSetter;

    private function existingProduct(): Product
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', 'Old description', Money::fromMinorUnits(1000, Currency::USD), 5);
        self::setId($product, 1);

        return $product;
    }

    public function testUpdatesNameAndDescription(): void
    {
        $product = $this->existingProduct();
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $cmd = new UpdateProductCommand(name: 'New Name');
        $cmd->id = 1;

        $handler = new UpdateProductHandler($products);
        $response = $handler->handle($cmd);

        self::assertSame('New Name', $response->name);
        self::assertSame('Old description', $response->description);
    }

    public function testChangesPriceKeepingCurrencyWhenNotProvided(): void
    {
        $product = $this->existingProduct();
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $cmd = new UpdateProductCommand(priceAmountMinorUnits: 2500);
        $cmd->id = 1;

        $handler = new UpdateProductHandler($products);
        $response = $handler->handle($cmd);

        self::assertSame(2500, $response->priceAmountMinorUnits);
        self::assertSame('USD', $response->priceCurrency);
    }

    public function testChangesPriceAndCurrencyWhenBothProvided(): void
    {
        $product = $this->existingProduct();
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn($product);
        $products->method('save')->willReturnArgument(0);

        $cmd = new UpdateProductCommand(priceAmountMinorUnits: 3000, priceCurrency: 'EUR');
        $cmd->id = 1;

        $handler = new UpdateProductHandler($products);
        $response = $handler->handle($cmd);

        self::assertSame(3000, $response->priceAmountMinorUnits);
        self::assertSame('EUR', $response->priceCurrency);
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findById')->willReturn(null);

        $cmd = new UpdateProductCommand();
        $cmd->id = 999;

        $handler = new UpdateProductHandler($products);

        $this->expectException(ProductNotFoundException::class);

        $handler->handle($cmd);
    }
}
