<?php

declare(strict_types=1);

namespace App\Application\Auth\EventHandler;

use App\Application\Auth\Security\ActivationLinkSigner;
use App\Application\Shared\Mail\MailerInterface;
use App\Application\Shared\Outbox\AuditContextInterface;
use App\Application\Shared\Outbox\EmailIdempotencyGuardInterface;
use App\Domain\Auth\Event\UserCreated;

final class SendWelcomeEmailOnUserCreated
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ActivationLinkSigner $activationLinkSigner,
        private readonly string $appUrl,
        private readonly EmailIdempotencyGuardInterface $emailGuard,
        private readonly AuditContextInterface $auditContext,
    ) {
    }

    public function handle(UserCreated $event): void
    {
        if (null === $event->userId) {
            return;
        }

        // The outbox only guarantees at-least-once delivery: a crash between the
        // send below succeeding and the outbox row being marked processed replays
        // this event, which would otherwise send the same welcome email twice.
        $outboxEventId = $this->auditContext->outboxEventId();
        if ($this->emailGuard->alreadySent($outboxEventId)) {
            return;
        }

        $activationUrl = $this->activationLinkSigner->createUrl(
            $this->appUrl,
            $event->userId,
            $event->activationVersion,
            time() + 86400,
        );

        $this->mailer->send(
            to: $event->email,
            subject: 'Activate your account',
            template: 'emails/welcome.html.twig',
            context: [
                'userEmail' => $event->email,
                'activationUrl' => $activationUrl,
            ],
        );

        $this->emailGuard->markSent($outboxEventId);
    }
}
