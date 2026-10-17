<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class ImpersonateUserCommand
{
    public function __construct(
        public string $targetUserId,
        public string $impersonatorEmail,
    ) {
    }
}
