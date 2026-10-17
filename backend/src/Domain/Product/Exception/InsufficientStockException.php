<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class InsufficientStockException extends BusinessRuleException
{
    public function __construct(int $requested, int $available)
    {
        parent::__construct(\sprintf(
            'Cannot reserve %d unit(s): only %d in stock',
            $requested,
            $available,
        ));
    }
}
