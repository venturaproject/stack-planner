<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Application\Shared\Event\DomainEventPublisherInterface;
use App\Domain\Shared\DomainEvent;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Symfony\Component\Uid\Uuid;

final class OutboxPublisher implements DomainEventPublisherInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly OutboxEventCodec $codec,
        private readonly AuditContext $auditContext,
    ) {
    }

    public function publish(DomainEvent $event): void
    {
        $this->connection->insert('outbox_events', [
            'id' => Uuid::v7()->toRfc4122(),
            'event_type' => $event::class,
            'payload' => json_encode($this->codec->encode($event), \JSON_THROW_ON_ERROR),
            'context' => json_encode($this->auditContext->capture(), \JSON_THROW_ON_ERROR),
            'occurred_at' => $event->occurredAt->format('Y-m-d H:i:sP'),
            'available_at' => (new DateTimeImmutable())->format('Y-m-d H:i:sP'),
        ]);
    }
}
