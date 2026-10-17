<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;

abstract class DomainEvent
{
    public readonly DateTimeImmutable $occurredAt;

    public function __construct()
    {
        $this->occurredAt = new DateTimeImmutable();
    }
}
