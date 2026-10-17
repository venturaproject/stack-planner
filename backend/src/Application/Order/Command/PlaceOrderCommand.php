<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

final class PlaceOrderCommand
{
    public function __construct(public int $orderId)
    {
    }
}
