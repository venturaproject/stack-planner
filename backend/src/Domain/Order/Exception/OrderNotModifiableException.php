<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Shared\Exception\BusinessRuleException;

class OrderNotModifiableException extends BusinessRuleException
{
    public function __construct(OrderStatus $currentStatus)
    {
        parent::__construct(\sprintf('Order cannot be modified while in "%s" status', $currentStatus->value));
    }
}
