<?php

declare(strict_types=1);

namespace App\Domain\Auth\Event;

use App\Domain\Shared\DomainEvent;

final class UserCreated extends DomainEvent
{
    public function __construct(
        public readonly ?string $userId,
        public readonly string $email,
        public readonly int $activationVersion,
    ) {
        parent::__construct();
    }
}
