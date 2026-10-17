<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

use App\Domain\Issue\Exception\IssueNotFoundException;
use App\Domain\Issue\Repository\IssueRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UpdateIssueHandler
{
    public function __construct(private readonly IssueRepositoryInterface $issues)
    {
    }

    /** @return array<string, mixed> */
    public function handle(UpdateIssueCommand $command): array
    {
        $issue = $this->issues->findById($command->id);
        if (null === $issue) {
            throw IssueNotFoundException::forId($command->id);
        }

        $dueAt = null !== $command->dueAt && '' !== $command->dueAt ? new DateTimeImmutable($command->dueAt) : null;
        $issue->updateDetails($command->title, $command->type, $command->priority, $command->description, $dueAt, $command->epicId, $command->sprintId, $command->storyPoints, $command->assigneeNames);
        $this->issues->save($issue);

        return ['id' => $issue->getId(), 'projectId' => $issue->getProjectId(), 'key' => $issue->getIssueKey(), 'title' => $issue->getTitle(), 'status' => $issue->getStatus(), 'priority' => $issue->getPriority(), 'type' => $issue->getType(), 'description' => $issue->getDescription(), 'dueAt' => $issue->getDueAt()?->format(\DATE_ATOM), 'epicId' => $issue->getEpicId(), 'sprintId' => $issue->getSprintId(), 'storyPoints' => $issue->getStoryPoints(), 'assigneeNames' => $issue->getAssigneeNames()];
    }
}
