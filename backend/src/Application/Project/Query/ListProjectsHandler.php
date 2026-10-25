<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

final class ListProjectsHandler
{
    public function __construct(private readonly ProjectReadModelInterface $projects)
    {
    }

    /** @return array{data: list<array{id: int|null, name: string, key: string, description: string|null, status: string, coverImage: string|null, color: string, startDate: string|null, endDate: string|null, issueCount: int, openIssueCount: int, completedIssueCount: int, activeSprintName: string|null, members: list<array{name: string, avatar: string|null, role: string}>, createdAt: string}>, meta: array{total: int}} */
    public function handle(ListProjectsQuery $query): array
    {
        $data = [];
        foreach ($this->projects->findVisibleTo($query->memberUserId) as $project) {
            $data[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'key' => $project->getProjectKey(),
                'description' => $project->getDescription(),
                'status' => $project->getStatus(),
                'coverImage' => $project->getCoverImage(),
                'color' => $project->getColor(),
                'startDate' => $project->getStartDate()?->format('Y-m-d'),
                'endDate' => $project->getEndDate()?->format('Y-m-d'),
                'issueCount' => $this->projects->countIssues((int) $project->getId()),
                'openIssueCount' => $this->projects->countIssues((int) $project->getId()) - $this->projects->countIssuesByStatus((int) $project->getId(), 'done'),
                'completedIssueCount' => $this->projects->countIssuesByStatus((int) $project->getId(), 'done'),
                'activeSprintName' => $this->projects->activeSprintName((int) $project->getId()),
                'members' => $this->projects->members((int) $project->getId()),
                'createdAt' => $project->getCreatedAt()->format(\DATE_ATOM),
            ];
        }

        return ['data' => $data, 'meta' => ['total' => \count($data)]];
    }
}
