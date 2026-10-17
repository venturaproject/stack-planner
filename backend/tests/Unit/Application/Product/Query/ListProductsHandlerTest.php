<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Query;

use App\Application\Product\Query\ListProductsHandler;
use App\Application\Product\Query\ListProductsQuery;
use App\Application\Product\Query\ProductReadModelInterface;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListProductsHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsPagedProductsWithStockStats(): void
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), 5);
        self::setId($product, 1);

        $products = $this->createStub(ProductReadModelInterface::class);
        $products->method('findPaginated')->willReturn([$product]);
        $products->method('countAll')->willReturnMap([
            ['search', null, 1],
            [null, null, 20],
        ]);
        $products->method('countLowStock')->willReturnMap([
            [5, 3],
            [0, 1],
        ]);

        $handler = new ListProductsHandler($products);

        $response = $handler->handle(new ListProductsQuery(1, 20, 'search'));

        self::assertCount(1, $response->data);
        self::assertSame(['total' => 20, 'lowStock' => 3, 'outOfStock' => 1], $response->stats);
    }
}
