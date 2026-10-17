<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Query;
use App\Application\Product\DTO\ProductResponse;
use App\Application\Product\Query\ListProductsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Application\Shared\DTO\PagedResponse;
use App\Infrastructure\ApiPlatform\Input\ProductSearchCriteria;
use App\Infrastructure\ApiPlatform\Processor\ProductSearchProcessor;
use PHPUnit\Framework\TestCase;

final class ProductSearchProcessorTest extends TestCase
{
    public function testMapsCriteriaToTheExistingProductQuery(): void
    {
        $criteria = new ProductSearchCriteria();
        $criteria->search = 'keyboard';
        $criteria->stockLevel = 'low_stock';
        $criteria->createdFrom = '2026-01-01';
        $criteria->createdTo = '2026-01-31';
        $criteria->priceMin = 100;
        $criteria->priceMax = 500;
        $criteria->page = 2;
        $criteria->itemsPerPage = 25;

        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::once())
            ->method('ask')
            ->with(self::callback(static function (ListProductsQuery $query): bool {
                return 2 === $query->page
                    && 25 === $query->size
                    && 'keyboard' === $query->search
                    && 'low_stock' === $query->stockLevel
                    && '2026-01-01 00:00:00' === $query->createdFrom?->format('Y-m-d H:i:s')
                    && '2026-01-31 23:59:59' === $query->createdTo?->format('Y-m-d H:i:s')
                    && 100 === $query->priceMin
                    && 500 === $query->priceMax;
            }))
            ->willReturn(new PagedResponse([
                new ProductResponse(1, 'KEY-001', 'Keyboard', null, 499, 'USD', 3, null, '2026-01-01T00:00:00+00:00'),
            ], 2, 25, 26, 2));

        $result = (new ProductSearchProcessor($queryBus))->process($criteria, new Query());

        self::assertSame(26.0, $result->getTotalItems());
        self::assertSame(1, $result->count());
    }
}
