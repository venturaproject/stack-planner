<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

use App\Application\Issue\DTO\IssueAttachmentDownloadResponse;

final class GetIssueAttachmentHandler
{
    public function __construct(private readonly IssueReadModelInterface $issues)
    {
    }

    public function handle(GetIssueAttachmentQuery $query): ?IssueAttachmentDownloadResponse
    {
        $attachment = $this->issues->findAttachmentById($query->attachmentId);
        if (null === $attachment) {
            return null;
        }

        $issue = $this->issues->findById($attachment->getIssueId());
        if (null === $issue || null === $issue->getId()) {
            return null;
        }

        return new IssueAttachmentDownloadResponse(
            $issue->getId(),
            $issue->getIssueKey(),
            $attachment->getFilename(),
            $attachment->getMimeType(),
        );
    }
}
