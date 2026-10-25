<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\Security\PasswordResetLinkSigner;
use App\Application\Shared\Mail\MailerInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;

/**
 * Deliberately silent for a non-existent or deactivated email — same shape as the
 * controller not distinguishing "wrong password" from "wrong email" on login: an
 * attacker probing this endpoint must not be able to tell which emails exist in the
 * system from the response.
 */
final class RequestPasswordResetHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly MailerInterface $mailer,
        private readonly PasswordResetLinkSigner $linkSigner,
        private readonly string $appUrl,
    ) {
    }

    public function handle(RequestPasswordResetCommand $cmd): void
    {
        $user = $this->users->findByEmail($cmd->email);
        if (null === $user || !$user->isActive()) {
            return;
        }

        $resetUrl = $this->linkSigner->createUrl(
            $this->appUrl,
            $user->requireId(),
            $user->getActivationVersion(),
            time() + 3600,
        );

        $this->mailer->send(
            to: $user->getEmail(),
            subject: 'Reset your password',
            template: 'emails/password_reset.html.twig',
            context: [
                'userEmail' => $user->getEmail(),
                'resetUrl' => $resetUrl,
            ],
        );
    }
}
