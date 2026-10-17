<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

use App\Application\Shared\Security\CurrentUserIdProviderInterface;
use App\Application\Shared\Transaction\TransactionManagerInterface;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Entity\ProjectMember;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use LogicException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreateProjectHandler
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly CurrentUserIdProviderInterface $currentUser,
        private readonly TransactionManagerInterface $transactionManager,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(CreateProjectCommand $command): array
    {
        $userId = $this->currentUser->currentUserId()
            ?? throw new LogicException('A project can only be created by an authenticated user.');

        return $this->transactionManager->run(function () use ($command, $userId): array {
            $project = Project::create($command->name, $command->key, $command->description, '/demo/project-nova.svg');
            $this->projects->save($project);
            $projectId = $project->getId()
                ?? throw new LogicException('The project must be persisted before adding its owner.');
            $this->members->save(new ProjectMember($projectId, $userId, 'owner'));

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
        });
    }
}
