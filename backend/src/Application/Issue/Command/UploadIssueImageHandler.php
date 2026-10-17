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

    /** @return array{url: string, attachment?: array<string, mixed>} */
    public function handle(UploadIssueImageCommand $command): array
    {
        $issue = null !== $command->issueId ? $this->issues->findById($command->issueId) : null;
        if (null !== $command->issueId && null === $issue) {
            throw IssueNotFoundException::forId($command->issueId);
        }

        $filename = bin2hex(random_bytes(20)).'.'.$command->extension;
        $directory = null !== $issue ? 'issue-attachments/'.$issue->getIssueKey() : 'issue-uploads';
        $this->storage->store($directory, $filename, $command->temporaryPath);
        $url = '/media/'.$directory.'/'.$filename;

        if (null === $issue) {
            return ['url' => $url];
        }

        $issueId = $issue->getId();
        if (null === $issueId) {
            throw new LogicException('The issue must be persisted before adding an image.');
        }

        $attachment = new IssueAttachment($issueId, $filename, $command->originalName, $command->mimeType, $command->sizeBytes);
        $this->attachments->save($attachment);

        return [
            'url' => $url,
            'attachment' => [
                'id' => $attachment->getId(),
                'issue_id' => $issue->getId(),
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
