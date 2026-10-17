<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Product\Query;

use App\Application\Product\Query\ExportProductsHandler;
use App\Application\Product\Query\ExportProductsQuery;
use App\Application\Product\Query\ProductReadModelInterface;
use App\Domain\Product\Entity\Product;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ExportProductsHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testExportsAllMatchingFilters(): void
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1999, Currency::USD), 5);
        self::setId($product, 1);

        $products = $this->createMock(ProductReadModelInterface::class);
        $products->expects(self::once())->method('findForExport')
            ->with('shirt', null, 10001)
            ->willReturn([$product]);
        $products->expects(self::never())->method('findByIds');

        $handler = new ExportProductsHandler($products);

        $result = $handler->handle(new ExportProductsQuery([], 'shirt', null, true));

        self::assertFalse($result->limitExceeded);
        self::assertSame(['TSH-0001', 'T-Shirt', '', '19.99', 'USD', 5], $result->rows[0]);
    }

    public function testExportsOnlySelectedIds(): void
    {
        $product = Product::create(new Sku('TSH-0001'), 'T-Shirt', null, Money::fromMinorUnits(1000, Currency::USD), 5);
        self::setId($product, 1);

        $products = $this->createMock(ProductReadModelInterface::class);
        $products->expects(self::once())->method('findByIds')->with([1])->willReturn([$product]);
        $products->expects(self::never())->method('findForExport');

        $handler = new ExportProductsHandler($products);

        $result = $handler->handle(new ExportProductsQuery([1], null, null, false));

        self::assertCount(1, $result->rows);
    }
}
