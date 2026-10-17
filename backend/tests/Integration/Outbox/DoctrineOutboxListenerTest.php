<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use App\Infrastructure\Outbox\AuditContext;
use App\Tests\Support\DatabaseKernelTestCase;

/**
 * Verifies the postPersist/postUpdate/postRemove hook that writes aggregate-raised
 * events to the outbox as a side effect of Doctrine's own flush() — the mechanism
 * every EventSubscriberInterface handler in this app ultimately depends on, and
 * (before this test) the one piece of it with no coverage at all.
 */
final class DoctrineOutboxListenerTest extends DatabaseKernelTestCase
{
    private NotificationRepositoryInterface $notifications;

    protected function setUp(): void
    {
        parent::setUp();
        $this->notifications = self::getContainer()->get(NotificationRepositoryInterface::class);
    }

    /** @return array<int, array<string, mixed>> */
    private function outboxRowsFor(string $eventType): array
    {
        return $this->em->getConnection()->fetchAllAssociative(
            'SELECT * FROM outbox_events WHERE event_type = :type ORDER BY occurred_at',
            ['type' => $eventType],
        );
    }

    public function testPostPersistWritesTheAggregatesRaisedEventToTheOutbox(): void
    {
        $notification = new Notification();
        $notification->setTitle('Hello');
        $notification->setMessage('World');
        $notification->setType('info');
        $notification->recordCreated();

        $this->notifications->save($notification);

        $rows = $this->outboxRowsFor('App\Domain\Notification\Event\NotificationCreated');
        self::assertCount(1, $rows);

        $payload = json_decode((string) $rows[0]['payload'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('Hello', $payload['title']);
        // The aggregate's id is only assigned by Doctrine's UUID generator at
        // persist() time — recordCreated() was called before that, when it was
        // still null — but DoctrineOutboxListener's postPersist hook runs after,
        // and fills the aggregate id into the payload from the entity itself.
        self::assertSame($notification->getId(), $payload['notificationId']);
        self::assertNull($rows[0]['processed_at']);
    }

    public function testWritesNothingWhenTheAggregateRaisedNoEvent(): void
    {
        $before = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM outbox_events');

        $notification = new Notification();
        $notification->setTitle('No event raised');
        $notification->setMessage('Nothing to see here');
        $notification->setType('info');
        // recordCreated() deliberately not called.
        $this->notifications->save($notification);

        $after = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM outbox_events');
        self::assertSame($before, $after);
    }

    public function testADerivedEventRaisedMidConsumptionInheritsTheRestoredContext(): void
    {
        // Simulates OutboxConsumer having restored the original publisher's context
        // (set()) before dispatching the event currently being handled. A handler
        // for THAT event can itself cause a second flush — e.g.
        // OnStockAdjustedHandler dispatching CreateNotificationCommand — and the
        // resulting derived event's outbox row must carry the SAME actor/IP, not
        // re-derive from this (empty, CLI-context) worker's own TokenStorage/
        // RequestStack.
        $auditContext = self::getContainer()->get(AuditContext::class);
        $auditContext->set(['actorId' => 'user-1', 'actorEmail' => 'me@example.com', 'ipAddress' => '203.0.113.5']);

        try {
            $notification = new Notification();
            $notification->setTitle('Derived');
            $notification->setMessage('Event');
            $notification->setType('info');
            $notification->recordCreated();

            $this->notifications->save($notification);
        } finally {
            $auditContext->clear();
        }

        $rows = $this->outboxRowsFor('App\Domain\Notification\Event\NotificationCreated');
        $row = end($rows);
        $context = json_decode((string) $row['context'], true, 512, \JSON_THROW_ON_ERROR);

        self::assertSame('user-1', $context['actorId']);
        self::assertSame('me@example.com', $context['actorEmail']);
        self::assertSame('203.0.113.5', $context['ipAddress']);
    }

    public function testWritesNothingForAnEntityThatDoesNotImplementHasDomainEvents(): void
    {
        // Sanity check on the listener's own guard clause: postPersist/postUpdate/
        // postRemove fire for every managed entity, not just aggregates that raise
        // events — Permission doesn't implement HasDomainEvents at all.
        $before = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM outbox_events');

        $permission = new Permission();
        $permission->setName('test.permission.'.uniqid());
        self::getContainer()->get(PermissionRepositoryInterface::class)->save($permission);

        $after = (int) $this->em->getConnection()->fetchOne('SELECT COUNT(*) FROM outbox_events');
        self::assertSame($before, $after);
    }
}
