<?php

declare(strict_types=1);

namespace App\Application\Shared\Outbox;

/**
 * Lets an Application event handler avoid resending an email for a redelivered
 * outbox event. See Infrastructure\Outbox\EmailIdempotencyGuard for the at-least-
 * once delivery caveat this only mitigates, not eliminates.
 */
interface EmailIdempotencyGuardInterface
{
    public function alreadySent(?string $outboxEventId): bool;

    public function markSent(?string $outboxEventId): void;
}
