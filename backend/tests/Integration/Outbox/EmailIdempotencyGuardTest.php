<?php

declare(strict_types=1);

namespace App\Tests\Integration\Outbox;

use App\Infrastructure\Outbox\EmailIdempotencyGuard;
use App\Tests\Support\DatabaseKernelTestCase;

final class EmailIdempotencyGuardTest extends DatabaseKernelTestCase
{
    private EmailIdempotencyGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = self::getContainer()->get(EmailIdempotencyGuard::class);
    }

    public function testMarkSentThenAlreadySentRoundTripsThroughTheRealTable(): void
    {
        self::assertFalse($this->guard->alreadySent('outbox-event-1'));

        $this->guard->markSent('outbox-event-1');

        self::assertTrue($this->guard->alreadySent('outbox-event-1'));
        // A different outbox event is unaffected.
        self::assertFalse($this->guard->alreadySent('outbox-event-2'));
    }

    public function testMarkSentTwiceForTheSameEventDoesNotThrow(): void
    {
        // The scenario markSent()'s own UniqueConstraintViolationException catch
        // exists for: two redelivery attempts of the same event both reach
        // markSent() (e.g. after each independently passed alreadySent() == false
        // moments apart). The second call must be a no-op, not an error.
        $this->guard->markSent('outbox-event-1');
        $this->guard->markSent('outbox-event-1');

        self::assertTrue($this->guard->alreadySent('outbox-event-1'));
    }
}
