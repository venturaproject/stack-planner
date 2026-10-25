<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Issue\Query\IssueReadModelInterface;
use App\Domain\Issue\Entity\Issue;
use App\Domain\Issue\Entity\IssueActivity;
use App\Domain\Issue\Entity\IssueAttachment;
use App\Domain\Issue\Entity\IssueChecklistItem;
use App\Domain\Issue\Entity\IssueComment;
use App\Domain\Issue\Entity\IssueLink;
use App\Domain\Issue\Entity\IssueWorklog;
use App\Domain\Issue\Repository\IssueRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Issue> */
final class DoctrineIssueReadModel extends ServiceEntityRepository implements IssueReadModelInterface, IssueRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Issue::class);
    }

    /**
     * @param int[]|null $visibleProjectIds
     *
     * @return Issue[]
     */
    public function findForBoard(?int $projectId, ?array $visibleProjectIds = null): array
    {
        $query = $this->createQueryBuilder('i')->orderBy('i.dueAt', 'ASC');
        if (null !== $visibleProjectIds) {
            if ([] === $visibleProjectIds) {
                return [];
            }
            $query->andWhere('i.projectId IN (:visibleProjectIds)')->setParameter('visibleProjectIds', $visibleProjectIds);
        }
        if (null !== $projectId) {
            $query->andWhere('i.projectId = :projectId')->setParameter('projectId', $projectId);
        }

        return $query->getQuery()->getResult();
    }

    public function nextNumberForProject(int $projectId): int
    {
        $issues = $this->findBy(['projectId' => $projectId]);
        $max = 0;
        foreach ($issues as $issue) {
            if (preg_match('/-(\d+)$/', $issue->getIssueKey(), $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $max + 1;
    }

    public function save(Issue $issue): void
    {
        $this->getEntityManager()->persist($issue);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id): ?Issue
    {
        return $this->find($id);
    }

    public function findAttachmentById(int $id): ?IssueAttachment
    {
        return $this->getEntityManager()->find(IssueAttachment::class, $id);
    }

    public function findForUpdate(int $id): ?Issue
    {
        return $this->find($id);
    }

    public function findComments(int $issueId): array
    {
        return $this->getEntityManager()->getRepository(IssueComment::class)->findBy(['issueId' => $issueId], ['createdAt' => 'ASC']);
    }

    public function findChecklist(int $issueId): array
    {
        return $this->getEntityManager()->getRepository(IssueChecklistItem::class)->findBy(['issueId' => $issueId], ['position' => 'ASC']);
    }

    public function findWorklogs(int $issueId): array
    {
        return $this->getEntityManager()->getRepository(IssueWorklog::class)->findBy(['issueId' => $issueId], ['loggedAt' => 'DESC']);
    }

    public function findLinks(int $issueId): array
    {
        return $this->getEntityManager()->getRepository(IssueLink::class)->findBy(['sourceIssueId' => $issueId]);
    }

    public function findActivities(int $issueId): array
    {
        return $this->getEntityManager()->getRepository(IssueActivity::class)->findBy(['issueId' => $issueId], ['createdAt' => 'DESC']);
    }

    public function findAttachments(int $issueId): array
    {
        return $this->getEntityManager()->getRepository(IssueAttachment::class)->findBy(['issueId' => $issueId], ['id' => 'ASC']);
    }
}
