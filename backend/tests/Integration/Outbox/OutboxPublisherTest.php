<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Domain\Auth\Event\UserLoggedIn;
use App\Infrastructure\Outbox\AuditContext;
use App\Infrastructure\Outbox\OutboxEventCodec;
use App\Infrastructure\Outbox\OutboxPublisher;
use App\Tests\Support\DatabaseKernelTestCase;
use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * OutboxPublisher is the second of the two ways an event reaches the outbox —
 * used by handlers that publish explicitly (LoginHandler, ImpersonateUserHandler)
 * rather than having it happen implicitly via DoctrineOutboxListener when an
 * aggregate is flushed. Its own raw insert had no direct test before this.
 */
final class OutboxPublisherTest extends DatabaseKernelTestCase
{
    public function testPublishInsertsAReadyToConsumeOutboxRow(): void
    {
        $auditContext = new AuditContext(
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(RequestStack::class),
        );
        $publisher = new OutboxPublisher($this->em->getConnection(), new OutboxEventCodec(), $auditContext);

        $event = new UserLoggedIn('user-1', 'me@example.com');
        $publisher->publish($event);

        $row = $this->em->getConnection()->fetchAssociative(
            'SELECT * FROM outbox_events WHERE event_type = :type',
            ['type' => UserLoggedIn::class],
        );

        self::assertNotFalse($row);
        self::assertNull($row['processed_at']);
        self::assertNull($row['failed_at']);
        self::assertSame(0, $row['attempts']);
        // Ready to be claimed immediately, not scheduled for later.
        self::assertLessThanOrEqual(new DateTimeImmutable(), new DateTimeImmutable($row['available_at']));

        $payload = json_decode((string) $row['payload'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('user-1', $payload['userId']);
        self::assertSame('me@example.com', $payload['email']);
        self::assertSame($event->occurredAt->format(DateTimeInterface::ATOM), $payload['occurredAt']);
    }
}
