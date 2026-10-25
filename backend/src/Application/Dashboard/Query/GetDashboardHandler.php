<?php

declare(strict_types=1);

namespace App\Application\Dashboard\Query;

use App\Application\Customer\Query\CustomerReadModelInterface;
use App\Application\Dashboard\DTO\DashboardResponse;
use App\Application\Order\Query\OrderReadModelInterface;
use App\Application\Product\Query\ProductReadModelInterface;
use App\Domain\Order\ValueObject\OrderStatus;

final class GetDashboardHandler
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function __construct(
        private readonly ProductReadModelInterface $products,
        private readonly CustomerReadModelInterface $customers,
        private readonly OrderReadModelInterface $orders,
    ) {
    }

    public function handle(GetDashboardQuery $query): DashboardResponse
    {
        return new DashboardResponse(
            totalProducts: $this->products->countAll(null),
            lowStockProducts: $this->products->countLowStock(self::LOW_STOCK_THRESHOLD),
            totalCustomers: $this->customers->countAll(null),
            totalOrders: $this->orders->countAll(null),
            draftOrders: $this->orders->countByStatus(OrderStatus::Draft),
            placedOrders: $this->orders->countByStatus(OrderStatus::Placed),
            cancelledOrders: $this->orders->countByStatus(OrderStatus::Cancelled),
        );
    }
}
