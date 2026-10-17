<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListProjectsHandler
{
    public function __construct(private readonly ProjectReadModelInterface $projects)
    {
    }

    /** @return array{data: list<array<string, mixed>>, meta: array{total: int}} */
    public function handle(ListProjectsQuery $query): array
    {
        $data = [];
        foreach ($this->projects->findAll() as $project) {
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
