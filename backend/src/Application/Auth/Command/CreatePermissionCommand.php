<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class CreatePermissionCommand
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {
    }
}
