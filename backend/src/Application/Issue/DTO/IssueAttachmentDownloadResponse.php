<?php

declare(strict_types=1);

namespace App\Application\Issue\DTO;

final readonly class IssueAttachmentDownloadResponse
{
    public function __construct(
        public int $issueId,
        public string $issueKey,
        public string $filename,
        public string $mimeType,
    ) {
    }
}
