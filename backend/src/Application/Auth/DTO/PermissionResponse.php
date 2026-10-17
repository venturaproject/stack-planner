<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

use App\Domain\Auth\Entity\Permission;

final readonly class PermissionResponse
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
    ) {
    }

    public static function from(Permission $permission): self
    {
        return new self(
            id: $permission->requireId(),
            name: $permission->getName(),
            description: $permission->getDescription(),
        );
    }
}
