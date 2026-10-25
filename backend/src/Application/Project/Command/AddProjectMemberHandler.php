<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Project\Entity\ProjectMember;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use App\Domain\Project\Repository\ProjectRepositoryInterface;

final class AddProjectMemberHandler
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /** @return array{projectId: int, userId: string, role: string} */
    public function handle(AddProjectMemberCommand $command): array
    {
        if (null === $this->projects->findById($command->projectId)) {
            throw ProjectNotFoundException::forId($command->projectId);
        }
        $user = $this->users->findByEmail($command->email);
        if (null === $user) {
            throw new UserNotFoundException();
        }
        $userId = $user->requireId();

        $member = $this->members->findForProjectAndUser($command->projectId, $userId)
            ?? new ProjectMember($command->projectId, $userId, $command->role);
        $member->changeRole($command->role);
        $this->members->save($member);

        return ['projectId' => $command->projectId, 'userId' => $userId, 'role' => $member->getRole()];
    }
}
