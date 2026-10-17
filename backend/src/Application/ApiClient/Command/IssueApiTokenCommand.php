<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

final readonly class IssueApiTokenCommand
{
    public function __construct(
        public string $clientId,
        public string $clientSecret,
    ) {
    }
}
