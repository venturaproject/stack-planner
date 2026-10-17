<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

use RuntimeException;

class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
