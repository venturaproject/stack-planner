<?php

declare(strict_types=1);

namespace App\Application\Shared\Mail;

final readonly class MailAttachment
{
    public function __construct(
        public string $path,
        public string $filename,
        public ?string $contentType = null,
    ) {
    }
}
