<?php

declare(strict_types=1);

namespace App\Application\ApiClient\Command;

final readonly class CreateApiClientCommand
{
    /** @param string[] $scopes */
    public function __construct(
        public string $name,
        public array $scopes,
    ) {
    }
}
