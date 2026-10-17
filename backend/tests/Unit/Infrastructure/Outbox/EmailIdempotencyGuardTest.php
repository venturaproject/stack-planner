<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Outbox;

use App\Infrastructure\Outbox\EmailIdempotencyGuard;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception as DriverException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Exception;
use PHPUnit\Framework\TestCase;

final class EmailIdempotencyGuardTest extends TestCase
{
    public function testAlreadySentIsFalseWithoutAnOutboxEventId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('fetchOne');

        self::assertFalse((new EmailIdempotencyGuard($connection))->alreadySent(null));
    }

    public function testAlreadySentReflectsWhetherARowExists(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::once())->method('fetchOne')->with(
            self::stringContains('sent_emails'),
            ['outboxEventId' => 'outbox-event-1'],
        )->willReturn(1);

        self::assertTrue((new EmailIdempotencyGuard($connection))->alreadySent('outbox-event-1'));
    }

    public function testMarkSentDoesNothingWithoutAnOutboxEventId(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(self::never())->method('insert');

        (new EmailIdempotencyGuard($connection))->markSent(null);
    }

    public function testMarkSentInsertsARow(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')->willReturnCallback(static fn (callable $fn) => $fn());
        $connection->expects(self::once())->method('insert')->with('sent_emails', self::callback(static function (array $row): bool {
            self::assertSame('outbox-event-1', $row['outbox_event_id']);
            self::assertArrayHasKey('id', $row);
            self::assertArrayHasKey('sent_at', $row);

            return true;
        }));

        (new EmailIdempotencyGuard($connection))->markSent('outbox-event-1');
    }

    public function testMarkSentToleratesLosingARaceToAnotherWorker(): void
    {
        // Must not throw: another worker already marking the same outbox event
        // sent, between this call's own alreadySent() check and this insert, is
        // the expected/fine outcome of a race, not a failure.
        self::expectNotToPerformAssertions();

        $driverException = new class('duplicate key') extends Exception implements DriverException {
            public function getSQLState(): ?string
            {
                return '23505';
            }
        };

        $connection = $this->createStub(Connection::class);
        $connection->method('transactional')->willThrowException(new UniqueConstraintViolationException($driverException, null));

        (new EmailIdempotencyGuard($connection))->markSent('outbox-event-1');
    }
}
