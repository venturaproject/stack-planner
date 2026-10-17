<?php

declare(strict_types=1);

namespace App\Domain\Auth\Event;

use App\Domain\Shared\DomainEvent;

final class RoleCreated extends DomainEvent
{
    public function __construct(
        public readonly ?string $roleId,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
