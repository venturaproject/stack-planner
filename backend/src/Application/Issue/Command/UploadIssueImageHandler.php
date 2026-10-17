<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

use App\Application\Shared\Storage\FileStorageInterface;
use App\Domain\Issue\Entity\IssueAttachment;
use App\Domain\Issue\Exception\IssueNotFoundException;
use App\Domain\Issue\Repository\IssueAttachmentRepositoryInterface;
use App\Domain\Issue\Repository\IssueRepositoryInterface;
use DateTimeImmutable;
use LogicException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UploadIssueImageHandler
{
    public function __construct(
        private readonly FileStorageInterface $storage,
        private readonly IssueRepositoryInterface $issues,
        private readonly IssueAttachmentRepositoryInterface $attachments,
    ) {
    }

    /** @return array{url: string, attachment: array<string, mixed>} */
    public function handle(UploadIssueImageCommand $command): array
    {
        $issue = $this->issues->findById($command->issueId);
        if (null === $issue) {
            throw IssueNotFoundException::forId($command->issueId);
        }

        $filename = bin2hex(random_bytes(20)).'.'.$command->extension;
        $directory = 'issue-attachments/'.$issue->getIssueKey();
        $this->storage->store($directory, $filename, $command->temporaryPath);

        $issueId = $issue->getId();
        if (null === $issueId) {
            throw new LogicException('The issue must be persisted before adding an image.');
        }

        $attachment = new IssueAttachment($issueId, $filename, $command->originalName, $command->mimeType, $command->sizeBytes);
        $this->attachments->save($attachment);
        $attachmentId = $attachment->getId()
            ?? throw new LogicException('The issue attachment must be persisted before returning its URL.');
        $url = \sprintf('/api/issues/%d/attachments/%d', $issueId, $attachmentId);

        return [
            'url' => $url,
            'attachment' => [
                'id' => $attachmentId,
                'issue_id' => $issueId,
                'user_id' => null,
                'path' => $directory.'/'.$filename,
                'original_name' => $attachment->getOriginalName(),
                'mime_type' => $attachment->getMimeType(),
                'size' => $attachment->getSizeBytes(),
                'user' => ['id' => null, 'name' => $command->authorName, 'email' => '', 'avatar' => null],
                'created_at' => (new DateTimeImmutable())->format(\DATE_ATOM),
                'updated_at' => (new DateTimeImmutable())->format(\DATE_ATOM),
            ],
        ];
    }
}
