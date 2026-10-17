<?php

declare(strict_types=1);

namespace App\Domain\Customer\Event;

use App\Domain\Shared\DomainEvent;

final class CustomerRegistered extends DomainEvent
{
    public function __construct(
        public readonly ?int $customerId,
        public readonly string $email,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
