<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Query;

use App\Application\Product\Query\GetProductByIdHandler;
use App\Application\Product\Query\GetProductByIdQuery;
use App\Application\Product\Query\ProductReadModelInterface;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class GetProductByIdHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsProductById(): void
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), 5);
        self::setId($product, 1);

        $products = $this->createStub(ProductReadModelInterface::class);
        $products->method('findById')->willReturn($product);

        $handler = new GetProductByIdHandler($products);

        $response = $handler->handle(new GetProductByIdQuery(1));

        self::assertSame(1, $response->id);
        self::assertSame('TSH-0001', $response->sku);
    }

    public function testThrowsWhenProductNotFound(): void
    {
        $products = $this->createStub(ProductReadModelInterface::class);
        $products->method('findById')->willReturn(null);

        $handler = new GetProductByIdHandler($products);

        $this->expectException(ProductNotFoundException::class);

        $handler->handle(new GetProductByIdQuery(999));
    }
}
