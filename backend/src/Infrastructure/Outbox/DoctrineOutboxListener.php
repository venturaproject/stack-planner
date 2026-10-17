<?php

declare(strict_types=1);

namespace App\Infrastructure\Outbox;

use App\Domain\Shared\HasDomainEvents;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use ReflectionClass;
use Symfony\Component\Uid\Uuid;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postRemove)]
final class DoctrineOutboxListener
{
    public function __construct(
        private readonly OutboxEventCodec $codec,
        private readonly AuditContext $auditContext,
    ) {
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->store($args);
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->store($args);
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->store($args);
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    private function store(LifecycleEventArgs $args): void
    {
        $aggregate = $args->getObject();
        if (!$aggregate instanceof HasDomainEvents) {
            return;
        }

        $aggregateName = (new ReflectionClass($aggregate))->getShortName();
        $rawId = method_exists($aggregate, 'getId') ? $aggregate->getId() : null;
        $aggregateId = \is_string($rawId) || \is_int($rawId) ? $rawId : null;
        $context = $this->auditContext->capture();
        $connection = $args->getObjectManager()->getConnection();

        foreach ($aggregate->pullEvents() as $event) {
            $connection->insert('outbox_events', [
                'id' => Uuid::v7()->toRfc4122(),
                'event_type' => $event::class,
                'payload' => json_encode($this->codec->encode($event, $aggregateName, $aggregateId), \JSON_THROW_ON_ERROR),
                'context' => json_encode($context, \JSON_THROW_ON_ERROR),
                'occurred_at' => $event->occurredAt->format('Y-m-d H:i:sP'),
                'available_at' => (new DateTimeImmutable())->format('Y-m-d H:i:sP'),
            ]);
        }
    }
}
