<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Command;

use App\Application\Product\Command\CreateProductCommand;
use App\Application\Product\Command\CreateProductHandler;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\DuplicateSkuException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class CreateProductHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testCreatesProduct(): void
    {
        $products = $this->createStub(ProductRepositoryInterface::class);
        $products->method('findBySku')->willReturn(null);
        $products->method('save')->willReturnCallback(static function (Product $product) {
            self::setId($product, 1);

            return $product;
        });

        $handler = new CreateProductHandler($products);

        $response = $handler->handle(new CreateProductCommand(
            sku: 'TSH-0001',
            name: 'T-Shirt',
            description: 'A shirt',
            priceAmountMinorUnits: 1999,
            priceCurrency: 'USD',
            initialStock: 10,
        ));

        self::assertSame(1, $response->id);
        self::assertSame('TSH-0001', $response->sku);
        self::assertSame(10, $response->stockQuantity);
    }

    public function testThrowsWhenSkuAlreadyExists(): void
    {
        $products = $this->createMock(ProductRepositoryInterface::class);
        $products->method('findBySku')->willReturn(
            Product::create(new Sku('TSH-0001'), 'Existing', null, Money::fromMinorUnits(100, Currency::USD), 0)
        );
        $products->expects(self::never())->method('save');

        $handler = new CreateProductHandler($products);

        $this->expectException(DuplicateSkuException::class);

        $handler->handle(new CreateProductCommand(sku: 'TSH-0001', name: 'T-Shirt'));
    }
}
