<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class TotpSetupNotInitiatedException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('No pending TOTP setup to confirm — call setup first');
    }
}
