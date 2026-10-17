<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\Query;

use App\Application\Order\Query\ListOrdersHandler;
use App\Application\Order\Query\ListOrdersQuery;
use App\Application\Order\Query\OrderReadModelInterface;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Tests\Support\EntityIdSetter;
use PHPUnit\Framework\TestCase;

final class ListOrdersHandlerTest extends TestCase
{
    use EntityIdSetter;

    public function testReturnsPagedOrdersWithStatusStats(): void
    {
        $order = Order::startDraft(1, null);
        self::setId($order, 1);

        $orders = $this->createStub(OrderReadModelInterface::class);
        $orders->method('findPaginated')->willReturn([$order]);
        $orders->method('countAll')->willReturnMap([
            [1, OrderStatus::Draft, 1],
            [null, null, 10],
        ]);
        $orders->method('countByStatus')->willReturnMap([
            [OrderStatus::Draft, 6],
            [OrderStatus::Placed, 3],
            [OrderStatus::Cancelled, 1],
        ]);

        $handler = new ListOrdersHandler($orders);

        $response = $handler->handle(new ListOrdersQuery(1, 20, 1, 'draft'));

        self::assertCount(1, $response->data);
        self::assertSame(['total' => 10, 'draft' => 6, 'placed' => 3, 'cancelled' => 1], $response->stats);
    }
}
