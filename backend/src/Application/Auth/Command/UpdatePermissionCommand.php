<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class UpdatePermissionCommand
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
    ) {
    }
}
