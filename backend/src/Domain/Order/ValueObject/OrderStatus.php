<?php

declare(strict_types=1);

namespace App\Domain\Order\ValueObject;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Placed = 'placed';
    case Cancelled = 'cancelled';
}
