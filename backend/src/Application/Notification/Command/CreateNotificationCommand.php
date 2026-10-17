<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

final readonly class CreateNotificationCommand
{
    public function __construct(
        public string $title,
        public string $message,
        public string $type,
        public ?string $userId,
        /**
         * Set by outbox-driven event subscribers (OnUserCreatedHandler,
         * OnOrderPlacedHandler, OnStockAdjustedHandler) so redelivery of the
         * triggering event can't create a duplicate notification. Left null for any
         * future non-event-driven caller — dedup is skipped rather than enforced
         * on a value that wouldn't mean anything there.
         */
        public ?string $outboxEventId = null,
    ) {
    }
}
