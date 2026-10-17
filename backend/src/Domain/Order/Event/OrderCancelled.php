<?php

declare(strict_types=1);

namespace App\Domain\Order\Event;

use App\Domain\Shared\DomainEvent;

final class OrderCancelled extends DomainEvent
{
    public function __construct(public readonly ?int $orderId)
    {
        parent::__construct();
    }
}
