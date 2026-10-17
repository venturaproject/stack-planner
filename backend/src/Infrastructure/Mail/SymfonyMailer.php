<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

use App\Application\Shared\Mail\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface as SymfonyMailerInterface;

/**
 * Composing the TemplatedEmail (from address, attachments) is deliberately kept
 * out of Application/EventHandler classes — see MailerInterface's docblock.
 * MailerInterface::send() is queued rather than blocking the caller: see
 * config/packages/messenger.php's routing for Symfony\Component\Mailer\Messenger\SendEmailMessage.
 */
final class SymfonyMailer implements MailerInterface
{
    public function __construct(
        private readonly SymfonyMailerInterface $mailer,
        #[Autowire(env: 'MAILER_FROM_ADDRESS')]
        private readonly string $fromAddress,
    ) {
    }

    public function send(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = [],
    ): void {
        $email = (new TemplatedEmail())
            ->from($this->fromAddress)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        foreach ($attachments as $attachment) {
            $email->attachFromPath($attachment->path, $attachment->filename, $attachment->contentType);
        }

        $this->mailer->send($email);
    }
}
