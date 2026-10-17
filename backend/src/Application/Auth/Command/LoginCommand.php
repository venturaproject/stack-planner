<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class LoginCommand
{
    public function __construct(
        public string $email,
        public string $password,

        /** Required on the second call to login() once totpRequired has been signaled. */
        public ?string $totpCode = null,
        public ?string $backupCode = null,
    ) {
    }
}
