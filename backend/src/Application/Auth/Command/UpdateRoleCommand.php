<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final readonly class UpdateRoleCommand
{
    /** @param string[] $permissionIds */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public array $permissionIds = [],
    ) {
    }
}
