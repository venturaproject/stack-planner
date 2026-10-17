<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use RuntimeException;

/**
 * Not really an error — a login flow-control signal meaning "credentials were
 * correct, now submit a TOTP/backup code". Caught explicitly in AuthController
 * rather than left to the generic ExceptionListener, since the response needs a
 * distinct shape ({"totpRequired": true}) for the frontend to branch on.
 */
final class TotpRequiredException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A TOTP code is required to complete login');
    }
}
