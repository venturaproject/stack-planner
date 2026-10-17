<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class InvalidTotpCodeException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Invalid or expired code');
    }
}
