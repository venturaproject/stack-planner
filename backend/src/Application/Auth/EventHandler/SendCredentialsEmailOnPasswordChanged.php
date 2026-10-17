<?php

declare(strict_types=1);

namespace App\Application\Auth\EventHandler;

use App\Application\Shared\Mail\MailerInterface;
use App\Application\Shared\Outbox\AuditContextInterface;
use App\Application\Shared\Outbox\EmailIdempotencyGuardInterface;
use App\Domain\Auth\Event\UserPasswordChanged;

final class SendCredentialsEmailOnPasswordChanged
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly EmailIdempotencyGuardInterface $emailGuard,
        private readonly AuditContextInterface $auditContext,
    ) {
    }

    public function handle(UserPasswordChanged $event): void
    {
        // The outbox only guarantees at-least-once delivery: a crash between the
        // send below succeeding and the outbox row being marked processed replays
        // this event, which would otherwise send the same notice twice.
        $outboxEventId = $this->auditContext->outboxEventId();
        if ($this->emailGuard->alreadySent($outboxEventId)) {
            return;
        }

        $this->mailer->send(
            to: $event->email,
            subject: 'Your password has been changed',
            template: 'emails/password_changed.html.twig',
            context: [
                'userEmail' => $event->email,
            ],
        );

        $this->emailGuard->markSent($outboxEventId);
    }
}
