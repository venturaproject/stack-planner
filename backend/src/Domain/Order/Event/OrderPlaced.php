<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Shared\DomainEvent;

final class OrderPlaced extends DomainEvent
{
    public function __construct(
        public readonly ?int $orderId,
        public readonly int $customerId,
        public readonly int $totalAmountMinorUnits,
        public readonly string $currency,
    ) {
        parent::__construct();
    }
}
