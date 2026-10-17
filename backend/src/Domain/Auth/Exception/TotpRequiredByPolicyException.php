<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class TotpRequiredByPolicyException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('Two-factor authentication is required by your organization and cannot be disabled.');
    }
}
