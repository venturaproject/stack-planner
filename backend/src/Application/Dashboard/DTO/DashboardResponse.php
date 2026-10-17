<?php

declare(strict_types=1);

namespace App\Application\Dashboard\DTO;

final readonly class DashboardResponse
{
    public function __construct(
        public int $totalProducts,
        public int $lowStockProducts,
        public int $totalCustomers,
        public int $totalOrders,
        public int $draftOrders,
        public int $placedOrders,
        public int $cancelledOrders,
    ) {
    }
}
