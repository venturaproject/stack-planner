<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Shared\DomainEvent;

/**
 * Raised on every AddOrderLineHandler call but currently has no subscriber —
 * intentional, not an oversight: no audit trail or side effect is wanted at the
 * individual-line-item level (OrderPlaced already covers the order as a whole).
 * Still goes through the outbox like any other domain event, so it's here and
 * ready the moment something legitimately needs to react to it.
 */
final class OrderLineAdded extends DomainEvent
{
    public function __construct(
        public readonly ?int $orderId,
        public readonly int $productId,
        public readonly int $quantity,
    ) {
        parent::__construct();
    }
}
