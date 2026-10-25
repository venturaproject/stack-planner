<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use App\Domain\Project\Repository\ProjectRepositoryInterface;

final class ListProjectMembersHandler
{
    public function __construct(
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly UserRepositoryInterface $users,
        private readonly ProjectRepositoryInterface $projects,
    ) {
    }

    /** @return list<array{userId: string, name: string, avatarUrl: ?string, role: string}> */
    public function handle(ListProjectMembersQuery $query): array
    {
        if (null === $this->projects->findById($query->projectId)) {
            throw ProjectNotFoundException::forId($query->projectId);
        }

        $result = [];
        foreach ($this->members->findForProject($query->projectId) as $member) {
            $user = $this->users->findById($member->getUserId());
            if (null !== $user) {
                $result[] = [
                    'userId' => $user->requireId(),
                    'name' => trim($user->getFirstName().' '.$user->getLastName()),
                    'avatarUrl' => $user->getAvatarUrl(),
                    'role' => $member->getRole(),
                ];
            }
        }

        return $result;
    }
}
