<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

use App\Domain\Issue\Exception\IssueNotFoundException;
use App\Domain\Issue\Repository\IssueRepositoryInterface;

final class UpdateIssueStatusHandler
{
    public function __construct(private readonly IssueRepositoryInterface $issues)
    {
    }

    /** @return array{id: int|null, status: string} */
    public function handle(UpdateIssueStatusCommand $command): array
    {
        $issue = $this->issues->findById($command->id);
        if (null === $issue) {
            throw IssueNotFoundException::forId($command->id);
        }

        $issue->changeStatus($command->status);
        $this->issues->save($issue);

        return ['id' => $issue->getId(), 'status' => $issue->getStatus()];
    }
}
