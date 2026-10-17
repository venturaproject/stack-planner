<?php

declare(strict_types=1);

namespace App\Application\Shared\Mail;

/**
 * Application-layer port for sending templated emails. Keeps event handlers
 * ignorant of Symfony Mailer/Twig/MAILER_FROM_ADDRESS — see
 * Infrastructure\Mail\SymfonyMailer for the adapter.
 */
interface MailerInterface
{
    /**
     * @param array<string, mixed> $context     Variables passed to the Twig template
     * @param MailAttachment[]     $attachments
     */
    public function send(
        string $to,
        string $subject,
        string $template,
        array $context = [],
        array $attachments = [],
    ): void;
}
