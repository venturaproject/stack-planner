<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

final readonly class UpdateOrderLineQuantityCommand
{
    public function __construct(
        public int $orderId,
        public int $lineId,
        public int $quantity,
    ) {
    }
}
