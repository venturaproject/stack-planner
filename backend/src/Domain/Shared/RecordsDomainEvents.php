<?php

declare(strict_types=1);

namespace App\Domain\Shared;

trait RecordsDomainEvents
{
    /** @var DomainEvent[] */
    private array $domainEvents = [];

    protected function raise(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return DomainEvent[] */
    public function pullEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
