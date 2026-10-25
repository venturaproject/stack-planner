<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

use App\Domain\Project\Exception\CannotRemoveLastProjectOwnerException;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use App\Domain\Project\Repository\ProjectRepositoryInterface;

final class RemoveProjectMemberHandler
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
    ) {
    }

    public function handle(RemoveProjectMemberCommand $command): void
    {
        if (null === $this->projects->findById($command->projectId)) {
            throw ProjectNotFoundException::forId($command->projectId);
        }

        $member = $this->members->findForProjectAndUser($command->projectId, $command->userId);
        if (null === $member) {
            return;
        }
        if ('owner' === $member->getRole() && 1 >= $this->members->countOwners($command->projectId)) {
            throw new CannotRemoveLastProjectOwnerException();
        }

        $this->members->remove($member);
    }
}
