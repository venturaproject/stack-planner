<?php

declare(strict_types=1);

namespace App\Application\Issue\Query;

final class ListIssuesHandler
{
    public function __construct(private readonly IssueReadModelInterface $issues)
    {
    }

    /** @return array{data: list<array{id: int|null, projectId: int, key: string, title: string, status: string, priority: string, dueAt: string|null, startAt: string|null, epicId: int|null, sprintId: int|null, type: string, storyPoints: int|null, reporterName: string, assigneeNames: list<string>, createdAt: string, description: string|null}>} */
    public function handle(ListIssuesQuery $query): array
    {
        $data = [];
        foreach ($this->issues->findForBoard($query->projectId, $query->visibleProjectIds) as $issue) {
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
