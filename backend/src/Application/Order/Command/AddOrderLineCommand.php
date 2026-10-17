<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

final class AddOrderLineCommand
{
    public function __construct(
        public ?int $orderId = null,
        public ?int $customerId = null,
        public int $productId = 0,
        public int $quantity = 1,
    ) {
    }
}
