<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class CreateRoleCommand
{
    /** @param string[] $permissionIds */
    public function __construct(
        public string $name,
        public ?string $description,
        public array $permissionIds = [],
    ) {
    }
}
