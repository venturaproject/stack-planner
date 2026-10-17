<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

use App\Domain\Shared\ValueObject\Currency;

class CurrencyMismatchException extends BusinessRuleException
{
    public function __construct(Currency $expected, Currency $actual)
    {
        parent::__construct(\sprintf(
            'Currency mismatch: expected %s, got %s',
            $expected->value,
            $actual->value,
        ));
    }
}
