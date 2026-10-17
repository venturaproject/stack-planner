<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Query;

use App\Application\Order\Query\ExportOrdersHandler;
use App\Application\Order\Query\ExportOrdersQuery;
use App\Application\Order\Query\OrderReadModelInterface;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ExportOrdersHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testExportsAllMatchingFilters(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createMock(OrderReadModelInterface::class);
        $orders->expects(self::once())->method('findForExport')
            ->with(1, OrderStatus::Draft, 10001)
            ->willReturn([$order]);
        $orders->expects(self::never())->method('findByIds');

        $handler = new ExportOrdersHandler($orders);

        $result = $handler->handle(new ExportOrdersQuery([], 1, 'draft', true));

        self::assertFalse($result->limitExceeded);
        self::assertSame([1, 1, 'draft', '', '', 0], $result->rows[0]);
    }

    public function testExportsOnlySelectedIds(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createMock(OrderReadModelInterface::class);
        $orders->expects(self::once())->method('findByIds')->with([1])->willReturn([$order]);
        $orders->expects(self::never())->method('findForExport');

        $handler = new ExportOrdersHandler($orders);

        $result = $handler->handle(new ExportOrdersQuery([1], null, null, false));

        self::assertCount(1, $result->rows);
    }
}
