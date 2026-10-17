<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

use App\Domain\Project\Entity\Project;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreateProjectHandler
{
    public function __construct(private readonly ProjectRepositoryInterface $projects)
    {
    }

    /** @return array<string, mixed> */
    public function handle(CreateProjectCommand $command): array
    {
        $project = Project::create($command->name, $command->key, $command->description, '/demo/project-nova.svg');
        $this->projects->save($project);

        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'key' => $project->getProjectKey(),
            'description' => $project->getDescription(),
            'status' => $project->getStatus(),
            'coverImage' => $project->getCoverImage(),
            'color' => $project->getColor(),
            'startDate' => null,
            'endDate' => null,
            'issueCount' => 0,
            'openIssueCount' => 0,
            'completedIssueCount' => 0,
            'activeSprintName' => null,
            'members' => [],
            'createdAt' => $project->getCreatedAt()->format(\DATE_ATOM),
        ];
    }
}
