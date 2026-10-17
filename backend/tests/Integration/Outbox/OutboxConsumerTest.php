<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Domain\Auth\Event\UserLoggedIn;
use App\Infrastructure\Outbox\AuditContext;
use App\Infrastructure\Outbox\OutboxConsumer;
use App\Infrastructure\Outbox\OutboxEventCodec;
use App\Infrastructure\Outbox\OutboxWorkerMustRestartException;
use App\Tests\Support\DatabaseKernelTestCase;
use DateTimeImmutable;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Exercises the actual claim/dispatch/mark-processed SQL against a real database —
 * SELECT ... FOR UPDATE SKIP LOCKED, the retry backoff, and the dead-letter cutoff
 * are exactly the kind of logic a mock can't meaningfully verify.
 */
final class OutboxConsumerTest extends DatabaseKernelTestCase
{
    private function insertRow(string $id, int $attempts = 0, ?string $failedAt = null, ?string $availableAt = null): void
    {
        $this->em->getConnection()->insert('outbox_events', [
            'id' => $id,
            'event_type' => UserLoggedIn::class,
            'payload' => json_encode(['userId' => 'user-1', 'email' => 'me@example.com', 'occurredAt' => '2026-01-01T00:00:00+00:00'], \JSON_THROW_ON_ERROR),
            'context' => '{}',
            'occurred_at' => '2026-01-01 00:00:00+00',
            'available_at' => $availableAt ?? '2026-01-01 00:00:00+00',
            'attempts' => $attempts,
            'failed_at' => $failedAt,
        ]);
    }

    private function row(string $id): array
    {
        return $this->em->getConnection()->fetchAssociative('SELECT * FROM outbox_events WHERE id = :id', ['id' => $id]);
    }

    private function consumer(EventDispatcherInterface $dispatcher): OutboxConsumer
    {
        return new OutboxConsumer(
            $this->em->getConnection(),
            new OutboxEventCodec(),
            new AuditContext($this->createStub(TokenStorageInterface::class), $this->createStub(RequestStack::class)),
            $dispatcher,
            $this->em,
        );
    }

    public function testConsumesAReadyEventAndMarksItProcessed(): void
    {
        $this->insertRow('11111111-1111-1111-1111-111111111111');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')->with(self::isInstanceOf(UserLoggedIn::class), UserLoggedIn::class);

        $consumed = $this->consumer($dispatcher)->consumeOne();

        self::assertTrue($consumed);
        $row = $this->row('11111111-1111-1111-1111-111111111111');
        self::assertNotNull($row['processed_at']);
        self::assertNull($row['locked_until']);
    }

    public function testReturnsFalseWhenNothingIsReady(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        self::assertFalse($this->consumer($dispatcher)->consumeOne());
    }

    public function testFailureReschedulesWithBackoffBelowMaxAttempts(): void
    {
        $this->insertRow('22222222-2222-2222-2222-222222222222', attempts: 2);

        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willThrowException(new RuntimeException('boom'));

        $consumed = $this->consumer($dispatcher)->consumeOne();

        self::assertTrue($consumed);
        $row = $this->row('22222222-2222-2222-2222-222222222222');
        self::assertSame(3, $row['attempts']);
        self::assertNull($row['failed_at']);
        self::assertNull($row['locked_until']);
        self::assertStringContainsString('boom', $row['last_error']);
        self::assertGreaterThan(new DateTimeImmutable(), new DateTimeImmutable($row['available_at']));
    }

    public function testExhaustingRetriesMarksTheEventFailedInsteadOfRetryingForever(): void
    {
        // MAX_ATTEMPTS is 10 (private const on OutboxConsumer) — a row already at 9
        // attempts is on its last try.
        $this->insertRow('33333333-3333-3333-3333-333333333333', attempts: 9);

        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willThrowException(new RuntimeException('permanently broken'));

        $this->consumer($dispatcher)->consumeOne();

        $row = $this->row('33333333-3333-3333-3333-333333333333');
        self::assertSame(10, $row['attempts']);
        self::assertNotNull($row['failed_at']);
        self::assertStringContainsString('permanently broken', $row['last_error']);
    }

    public function testFailedEventsAreNeverPickedUpAgain(): void
    {
        $this->insertRow('44444444-4444-4444-4444-444444444444', attempts: 10, failedAt: '2026-01-01 00:00:00+00');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        self::assertFalse($this->consumer($dispatcher)->consumeOne());
    }

    public function testThrowsWhenAFlushFailureLeavesTheEntityManagerClosed(): void
    {
        // Doctrine closes the EntityManager internally on any flush() failure —
        // simulated directly here (rather than trying to force a real flush
        // failure through a subscriber) since consumeOne()'s reaction to it
        // doesn't depend on what caused the closure, only on the fact that it
        // happened. See DoctrineAuditLogRepositoryTest/DoctrineNotificationRepositoryTest
        // for coverage of an actual caught UniqueConstraintViolationException
        // leaving it closed this same way.
        $this->insertRow('55555555-5555-5555-5555-555555555555');

        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(function () {
            $this->em->close();

            return null;
        });

        $this->expectException(OutboxWorkerMustRestartException::class);

        $this->consumer($dispatcher)->consumeOne();
    }

    public function testDoesNotThrowWhenTheEntityManagerIsStillOpenAfterDispatch(): void
    {
        $this->insertRow('66666666-6666-6666-6666-666666666666');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch');

        self::assertTrue($this->consumer($dispatcher)->consumeOne());
    }
}
