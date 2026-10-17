<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Project\Entity\ProjectMember;
use App\Domain\Project\Repository\ProjectMemberRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineProjectMemberRepository implements ProjectMemberRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /** @return ProjectMember[] */
    public function findForProject(int $projectId): array
    {
        return $this->entityManager->getRepository(ProjectMember::class)->findBy(['projectId' => $projectId]);
    }

    public function findForProjectAndUser(int $projectId, string $userId): ?ProjectMember
    {
        return $this->entityManager->getRepository(ProjectMember::class)->findOneBy([
            'projectId' => $projectId,
            'userId' => $userId,
        ]);
    }

    public function findProjectIdsForUser(string $userId): array
    {
        /** @var list<array{projectId: mixed}> $rows */
        $rows = $this->entityManager->createQueryBuilder()
            ->select('projectMember.projectId AS projectId')
            ->from(ProjectMember::class, 'projectMember')
            ->where('projectMember.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getScalarResult();

        $projectIds = [];
        foreach ($rows as $row) {
            $projectId = $row['projectId'] ?? null;
            if (\is_int($projectId)) {
                $projectIds[] = $projectId;
            } elseif (\is_string($projectId) && ctype_digit($projectId)) {
                $projectIds[] = (int) $projectId;
            }
        }

        return $projectIds;
    }

    public function countOwners(int $projectId): int
    {
        return $this->entityManager->getRepository(ProjectMember::class)->count([
            'projectId' => $projectId,
            'role' => 'owner',
        ]);
    }

    public function save(ProjectMember $member): void
    {
        $this->entityManager->persist($member);
        $this->entityManager->flush();
    }

    public function remove(ProjectMember $member): void
    {
        $this->entityManager->remove($member);
        $this->entityManager->flush();
    }
}
