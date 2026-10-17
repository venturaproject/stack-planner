<?php

declare(strict_types=1);

namespace App\Domain\Auth\Event;

use App\Domain\Shared\DomainEvent;

final class UserImpersonated extends DomainEvent
{
    public function __construct(
        public readonly string $userId,
        public readonly string $email,
        public readonly string $impersonatorEmail,
    ) {
        parent::__construct();
    }
}
