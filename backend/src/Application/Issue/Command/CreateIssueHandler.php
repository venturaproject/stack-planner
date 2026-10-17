<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

use App\Domain\Issue\Entity\Issue;
use App\Domain\Issue\Repository\IssueRepositoryInterface;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreateIssueHandler
{
    public function __construct(
        private readonly IssueRepositoryInterface $issues,
        private readonly ProjectRepositoryInterface $projects,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(CreateIssueCommand $command): array
    {
        $project = $this->projects->findById($command->projectId);
        if (null === $project) {
            throw ProjectNotFoundException::forId($command->projectId);
        }

        $key = $project->getProjectKey().'-'.$this->issues->nextNumberForProject($command->projectId);
        $dueAt = null !== $command->dueAt && '' !== $command->dueAt ? new DateTimeImmutable($command->dueAt) : null;
        $issue = new Issue($command->projectId, $key, $command->title, 'todo', $command->priority, $dueAt, $command->epicId, $command->sprintId, $command->type, $command->storyPoints, 'Admin User', $command->assigneeNames, null, $command->description);
        $this->issues->save($issue);

        return ['id' => $issue->getId(), 'projectId' => $issue->getProjectId(), 'key' => $issue->getIssueKey(), 'title' => $issue->getTitle(), 'status' => $issue->getStatus(), 'priority' => $issue->getPriority(), 'dueAt' => $issue->getDueAt()?->format(\DATE_ATOM), 'type' => $issue->getType(), 'storyPoints' => $issue->getStoryPoints()];
    }
}
