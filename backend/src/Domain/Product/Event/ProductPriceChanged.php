<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Shared\DomainEvent;

final class ProductPriceChanged extends DomainEvent
{
    public function __construct(
        public readonly ?int $productId,
        public readonly int $previousAmountMinorUnits,
        public readonly int $newAmountMinorUnits,
        public readonly string $currency,
    ) {
        parent::__construct();
    }
}
