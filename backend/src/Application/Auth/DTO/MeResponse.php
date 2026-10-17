<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

final readonly class MeResponse
{
    public function __construct(
        public UserResponse $user,
        public ?string $impersonatorEmail,
        public bool $mustSetupTotp = false,
    ) {
    }
}
