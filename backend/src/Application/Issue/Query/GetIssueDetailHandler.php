<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

use App\Domain\Issue\Exception\IssueNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class GetIssueDetailHandler
{
    public function __construct(private readonly IssueReadModelInterface $issues)
    {
    }

    /** @return array<string, mixed> */
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
        $attachments = array_map(static fn ($attachment): array => [
            'id' => $attachment->getId(), 'filename' => $attachment->getOriginalName(), 'mimeType' => $attachment->getMimeType(),
            'sizeBytes' => $attachment->getSizeBytes(), 'url' => '/demo/attachments/'.$issue->getIssueKey().'/'.$attachment->getFilename(),
        ], $this->issues->findAttachments($query->id));

        return [
            'id' => $issue->getId(), 'projectId' => $issue->getProjectId(), 'key' => $issue->getIssueKey(),
            'title' => $issue->getTitle(), 'status' => $issue->getStatus(), 'priority' => $issue->getPriority(),
            'dueAt' => $issue->getDueAt()?->format(\DATE_ATOM), 'startAt' => $issue->getStartAt()?->format(\DATE_ATOM), 'epicId' => $issue->getEpicId(), 'sprintId' => $issue->getSprintId(),
            'type' => $issue->getType(), 'storyPoints' => $issue->getStoryPoints(),
            'reporterName' => $issue->getReporterName(), 'assigneeNames' => $issue->getAssigneeNames(),
            'createdAt' => $issue->getCreatedAt()->format(\DATE_ATOM),
            'description' => $issue->getDescription(),
            'comments' => $comments, 'checklist' => $checklist, 'worklogs' => $worklogs, 'links' => $links, 'activities' => $activities, 'attachments' => $attachments,
        ];
    }
}
