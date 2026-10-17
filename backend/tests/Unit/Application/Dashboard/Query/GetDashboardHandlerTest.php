<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Dashboard\Query;

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Application\Dashboard\Query\GetDashboardHandler;
use App\Application\Dashboard\Query\GetDashboardQuery;
use App\Application\Order\Query\OrderReadModelInterface;
use App\Application\Product\Query\ProductReadModelInterface;
use App\Domain\Order\ValueObject\OrderStatus;
use PHPUnit\Framework\TestCase;

final class GetDashboardHandlerTest extends TestCase
{
    public function testAggregatesCountsFromEachRepository(): void
    {
        $products = $this->createStub(ProductReadModelInterface::class);
        $products->method('countAll')->willReturn(100);
        $products->method('countLowStock')->willReturn(7);

        $customers = $this->createStub(CustomerReadModelInterface::class);
        $customers->method('countAll')->willReturn(50);

        $orders = $this->createStub(OrderReadModelInterface::class);
        $orders->method('countAll')->willReturn(200);
        $orders->method('countByStatus')->willReturnMap([
            [OrderStatus::Draft, 20],
            [OrderStatus::Placed, 170],
            [OrderStatus::Cancelled, 10],
        ]);

        $handler = new GetDashboardHandler($products, $customers, $orders);

        $response = $handler->handle(new GetDashboardQuery());

        self::assertSame(100, $response->totalProducts);
        self::assertSame(7, $response->lowStockProducts);
        self::assertSame(50, $response->totalCustomers);
        self::assertSame(200, $response->totalOrders);
        self::assertSame(20, $response->draftOrders);
        self::assertSame(170, $response->placedOrders);
        self::assertSame(10, $response->cancelledOrders);
    }
}
