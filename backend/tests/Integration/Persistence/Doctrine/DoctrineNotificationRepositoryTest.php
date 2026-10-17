<?php

declare(strict_types=1);

namespace App\Tests\Integration\Persistence\Doctrine;

use App\Domain\Notification\Entity\Notification;
use App\Domain\Notification\Repository\NotificationRepositoryInterface;
use App\Tests\Support\DatabaseKernelTestCase;

final class DoctrineNotificationRepositoryTest extends DatabaseKernelTestCase
{
    private NotificationRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = self::getContainer()->get(NotificationRepositoryInterface::class);
    }

    public function testSavingASecondNotificationForAnAlreadyRecordedOutboxEventDoesNotThrow(): void
    {
        // The scenario this guards: CreateNotificationHandler's
        // findByOutboxEventId() dedup check has no lock backing it (unlike
        // AuditLogRecorder's), so two workers racing the same redelivered event
        // (e.g. one claimed it again after the other's lock lease expired
        // mid-processing) can both pass that check and both reach save(). The
        // unique index on outbox_event_id is the real guard; losing this race
        // must not surface as a failure — the notification already exists either
        // way.
        $first = new Notification();
        $first->setTitle('First');
        $first->setMessage('Message');
        $first->setType('info');
        $first->setOutboxEventId('racing-outbox-event');
        $this->repository->save($first);

        $second = new Notification();
        $second->setTitle('Second');
        $second->setMessage('Message');
        $second->setType('info');
        $second->setOutboxEventId('racing-outbox-event');

        // Must not throw.
        $result = $this->repository->save($second);

        self::assertSame($second, $result);
        // Doctrine's own behavior after any flush() exception, caught or not —
        // documented on the catch block this exercises, and on
        // OutboxWorkerMustRestartException, which is how OutboxConsumer reacts to
        // it in real usage.
        self::assertFalse($this->em->isOpen());
    }
}
