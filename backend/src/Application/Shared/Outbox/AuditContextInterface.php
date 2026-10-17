<?php

declare(strict_types=1);

namespace App\Application\Shared\Outbox;

/**
 * The only thing an Application-layer handler ever needs from the outbox's audit
 * context: the id of the outbox row currently being (re)delivered, so it can pass
 * it through for dedupe (see EmailIdempotencyGuardInterface, and
 * CreateNotificationCommand's own outboxEventId-based dedupe). Capturing and
 * restoring the actor/IP context is an Infrastructure concern driven by
 * OutboxConsumer — Application only ever reads outboxEventId().
 */
interface AuditContextInterface
{
    public function outboxEventId(): ?string;
}
