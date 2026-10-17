<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Shared\DomainEvent;

final class StockAdjusted extends DomainEvent
{
    public function __construct(
        public readonly ?int $productId,
        public readonly int $delta,
        public readonly int $newQuantity,
        public readonly string $reason,
    ) {
        parent::__construct();
    }
}
