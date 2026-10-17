<?php

declare(strict_types=1);

namespace App\Application\Project\Query;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListProjectMembersHandler
{
    public function __construct(
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    /** @return list<array{userId: string, name: string, avatarUrl: ?string, role: string}> */
    public function handle(ListProjectMembersQuery $query): array
    {
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
