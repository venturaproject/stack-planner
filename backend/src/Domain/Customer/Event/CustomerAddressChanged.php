<?php

declare(strict_types=1);

namespace App\Domain\Customer\Event;

use App\Domain\Shared\DomainEvent;

final class CustomerAddressChanged extends DomainEvent
{
    public function __construct(public readonly ?int $customerId)
    {
        parent::__construct();
    }
}
