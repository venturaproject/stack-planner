<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

final readonly class TotpSetupResponse
{
    public function __construct(
        public string $secret,
        public string $provisioningUri,
    ) {
    }
}
