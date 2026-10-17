<?php

declare(strict_types=1);

namespace App\Application\Shared\Event;

use App\Domain\Shared\DomainEvent;

interface DomainEventPublisherInterface
{
    public function publish(DomainEvent $event): void;
}
