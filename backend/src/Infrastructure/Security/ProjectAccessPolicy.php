<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Issue\Entity\Issue;
use App\Domain\Issue\Repository\IssueRepositoryInterface;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProjectAccessPolicy
{
    public function __construct(
        private readonly Security $security,
        private readonly ProjectMemberRepositoryInterface $members,
        private readonly IssueRepositoryInterface $issues,
    ) {
    }

    /**
     * Null means unrestricted admin scope; an empty list means no project memberships.
     *
     * @return int[]|null
     */
    public function visibleProjectIds(): ?array
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return null;
        }

        $userId = $this->currentUserId();

        return null === $userId ? [] : $this->members->findProjectIdsForUser($userId);
    }

    public function memberUserIdOrNullForProjectList(): ?string
    {
        return $this->security->isGranted('ROLE_ADMIN') ? null : ($this->currentUserId() ?? '');
    }

    public function currentUserId(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof SecurityUser ? $user->domainUser()->getId() : null;
    }

    public function assertProjectAccess(int $projectId): void
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return;
        }

        $userId = $this->currentUserId();
        if (null === $userId || null === $this->members->findForProjectAndUser($projectId, $userId)) {
            throw new NotFoundHttpException();
        }
    }

    public function assertIssueAccess(int $issueId): Issue
    {
        $issue = $this->issues->findById($issueId);
        if (null === $issue) {
            throw new NotFoundHttpException();
        }

        $this->assertProjectAccess($issue->getProjectId());

        return $issue;
    }
}
