<?php

declare(strict_types=1);

namespace App\Domain\Auth\Event;

use App\Domain\Shared\DomainEvent;

final class RolePermissionsChanged extends DomainEvent
{
    public function __construct(
        public readonly string $roleId,
        public readonly string $name,
        /** @var string[] */
        public readonly array $permissionNames,
    ) {
        parent::__construct();
    }
}
