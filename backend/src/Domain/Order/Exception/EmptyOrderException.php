<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class EmptyOrderException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Cannot place an order with no lines');
    }
}
