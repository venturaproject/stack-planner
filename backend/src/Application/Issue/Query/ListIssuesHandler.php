<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListIssuesHandler
{
    public function __construct(private readonly IssueReadModelInterface $issues)
    {
    }

    /** @return array{data: list<array<string, mixed>>} */
    public function handle(ListIssuesQuery $query): array
    {
        $data = [];
        foreach ($this->issues->findForBoard($query->projectId) as $issue) {
            $data[] = [
                'id' => $issue->getId(),
                'projectId' => $issue->getProjectId(),
                'key' => $issue->getIssueKey(),
                'title' => $issue->getTitle(),
                'status' => $issue->getStatus(),
                'priority' => $issue->getPriority(),
                'dueAt' => $issue->getDueAt()?->format(\DATE_ATOM),
                'startAt' => $issue->getStartAt()?->format(\DATE_ATOM),
                'epicId' => $issue->getEpicId(),
                'sprintId' => $issue->getSprintId(),
                'type' => $issue->getType(),
                'storyPoints' => $issue->getStoryPoints(),
                'reporterName' => $issue->getReporterName(),
                'assigneeNames' => $issue->getAssigneeNames(),
                'createdAt' => $issue->getCreatedAt()->format(\DATE_ATOM),
                'description' => $issue->getDescription(),
            ];
        }

        return ['data' => $data];
    }
}
