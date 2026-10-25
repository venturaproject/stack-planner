<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

use App\Domain\Issue\Exception\IssueNotFoundException;

final class GetIssueDetailHandler
{
    public function __construct(private readonly IssueReadModelInterface $issues)
    {
    }

    /**
     * @return array{
     *     id: int|null, projectId: int, key: string, title: string, status: string, priority: string,
     *     dueAt: string|null, startAt: string|null, epicId: int|null, sprintId: int|null, type: string,
     *     storyPoints: int|null, reporterName: string, assigneeNames: list<string>, createdAt: string,
     *     description: string, comments: array<int|string, array{id: int|null, authorName: string, content: string, createdAt: string}>,
     *     checklist: array<int|string, array{id: int|null, text: string, isDone: bool, position: int}>,
     *     worklogs: array<int|string, array{id: int|null, authorName: string, minutes: int, description: string, loggedAt: string}>,
     *     links: array<int|string, array{id: int|null, type: string, targetId: int|null, targetKey: string|null, targetTitle: string|null}>,
     *     activities: array<int|string, array{id: int|null, actorName: string, field: string, oldValue: string|null, newValue: string, createdAt: string}>,
     *     attachments: array<int|string, array{id: int|null, filename: string, mimeType: string, sizeBytes: int, url: string}>
     * }
     */
    public function handle(GetIssueDetailQuery $query): array
    {
        $issue = $this->issues->findById($query->id);
        if (null === $issue) {
            throw IssueNotFoundException::forId($query->id);
        }

        $comments = array_map(static fn ($comment): array => [
            'id' => $comment->getId(), 'authorName' => $comment->getAuthorName(),
            'content' => $comment->getContent(), 'createdAt' => $comment->getCreatedAt()->format(\DATE_ATOM),
        ], $this->issues->findComments($query->id));
        $checklist = array_map(static fn ($item): array => [
            'id' => $item->getId(), 'text' => $item->getText(), 'isDone' => $item->isDone(), 'position' => $item->getPosition(),
        ], $this->issues->findChecklist($query->id));
        $worklogs = array_map(static fn ($entry): array => [
            'id' => $entry->getId(), 'authorName' => $entry->getAuthorName(), 'minutes' => $entry->getMinutes(),
            'description' => $entry->getDescription(), 'loggedAt' => $entry->getLoggedAt()->format(\DATE_ATOM),
        ], $this->issues->findWorklogs($query->id));
        $links = array_map(function ($link): array {
            $target = $this->issues->findById($link->getTargetIssueId());

            return ['id' => $link->getId(), 'type' => $link->getLinkType(), 'targetId' => $target?->getId(), 'targetKey' => $target?->getIssueKey(), 'targetTitle' => $target?->getTitle()];
        }, $this->issues->findLinks($query->id));
        $activities = array_map(static fn ($activity): array => [
            'id' => $activity->getId(), 'actorName' => $activity->getActorName(), 'field' => $activity->getField(),
            'oldValue' => $activity->getOldValue(), 'newValue' => $activity->getNewValue(), 'createdAt' => $activity->getCreatedAt()->format(\DATE_ATOM),
        ], $this->issues->findActivities($query->id));
        $issueAttachments = $this->issues->findAttachments($query->id);
        $attachments = array_map(static fn ($attachment): array => [
            'id' => $attachment->getId(), 'filename' => $attachment->getOriginalName(), 'mimeType' => $attachment->getMimeType(),
            'sizeBytes' => $attachment->getSizeBytes(),
            'url' => \sprintf('/api/issues/%d/attachments/%d', $query->id, $attachment->getId()),
        ], $issueAttachments);

        $description = $issue->getDescription() ?? '';
        foreach ($issueAttachments as $attachment) {
            $privateUrl = \sprintf('/api/issues/%d/attachments/%d', $query->id, $attachment->getId());
            foreach ([
                '/demo/attachments/'.$issue->getIssueKey().'/'.$attachment->getFilename(),
                '/media/issue-attachments/'.$issue->getIssueKey().'/'.$attachment->getFilename(),
            ] as $legacyUrl) {
                $description = str_replace($legacyUrl, $privateUrl, $description);
            }
        }
        if (1 === preg_match_all('#/media/issue-uploads/([A-Za-z0-9_-]+\.(?:jpg|jpeg|png|gif|webp))#i', $description, $legacyUploads)) {
            foreach (array_unique($legacyUploads[1]) as $filename) {
                $description = str_replace(
                    '/media/issue-uploads/'.$filename,
                    \sprintf('/api/issues/%d/legacy-uploads/%s', $query->id, $filename),
                    $description,
                );
            }
        }

        return [
            'id' => $issue->getId(), 'projectId' => $issue->getProjectId(), 'key' => $issue->getIssueKey(),
            'title' => $issue->getTitle(), 'status' => $issue->getStatus(), 'priority' => $issue->getPriority(),
            'dueAt' => $issue->getDueAt()?->format(\DATE_ATOM), 'startAt' => $issue->getStartAt()?->format(\DATE_ATOM), 'epicId' => $issue->getEpicId(), 'sprintId' => $issue->getSprintId(),
            'type' => $issue->getType(), 'storyPoints' => $issue->getStoryPoints(),
            'reporterName' => $issue->getReporterName(), 'assigneeNames' => $issue->getAssigneeNames(),
            'createdAt' => $issue->getCreatedAt()->format(\DATE_ATOM),
            'description' => $description,
            'comments' => $comments, 'checklist' => $checklist, 'worklogs' => $worklogs, 'links' => $links, 'activities' => $activities, 'attachments' => $attachments,
        ];
    }
}
