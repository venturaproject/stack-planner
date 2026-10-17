<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Project\Query\ProjectReadModelInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Issue\Entity\Issue;
use App\Domain\Planning\Entity\Sprint;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\Entity\ProjectMember;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Project> */
final class DoctrineProjectRepository extends ServiceEntityRepository implements ProjectRepositoryInterface, ProjectReadModelInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('p')->orderBy('p.createdAt', 'DESC')->getQuery()->getResult();
    }

    public function findById(int $projectId): ?Project
    {
        return $this->find($projectId);
    }

    public function countIssues(int $projectId): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(i.id)')->from(Issue::class, 'i')
            ->where('i.projectId = :projectId')->setParameter('projectId', $projectId)
            ->getQuery()->getSingleScalarResult();
    }

    public function countIssuesByStatus(int $projectId, string $status): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(i.id)')->from(Issue::class, 'i')
            ->where('i.projectId = :projectId')->andWhere('i.status = :status')
            ->setParameter('projectId', $projectId)->setParameter('status', $status)
            ->getQuery()->getSingleScalarResult();
    }

    public function activeSprintName(int $projectId): ?string
    {
        $sprint = $this->getEntityManager()->getRepository(Sprint::class)->findOneBy(['projectId' => $projectId, 'status' => 'active']);

        return $sprint?->getName();
    }

    public function members(int $projectId): array
    {
        $members = $this->getEntityManager()->getRepository(ProjectMember::class)->findBy(['projectId' => $projectId]);
        $result = [];
        foreach ($members as $member) {
            $user = $this->getEntityManager()->getRepository(User::class)->find($member->getUserId());
            if (null !== $user) {
                $result[] = ['name' => $user->getFirstName().' '.$user->getLastName(), 'avatar' => $user->getAvatarUrl(), 'role' => $member->getRole()];
            }
        }

        return $result;
    }

    public function save(Project $project): void
    {
        $this->getEntityManager()->persist($project);
        $this->getEntityManager()->flush();
    }
}
