<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Application\Planning\Query\PlanningReadModelInterface;
use App\Domain\Planning\Entity\Epic;
use App\Domain\Planning\Entity\Sprint;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrinePlanningReadModel implements PlanningReadModelInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param int[]|null $visibleProjectIds
     *
     * @return Epic[]
     */
    public function findEpics(?int $projectId, ?array $visibleProjectIds = null): array
    {
        $query = $this->entityManager->createQueryBuilder()->select('e')->from(Epic::class, 'e')->orderBy('e.epicKey', 'ASC');
        if (null !== $visibleProjectIds) {
            if ([] === $visibleProjectIds) {
                return [];
            }
            $query->andWhere('e.projectId IN (:visibleProjectIds)')->setParameter('visibleProjectIds', $visibleProjectIds);
        }
        if (null !== $projectId) {
            $query->andWhere('e.projectId = :projectId')->setParameter('projectId', $projectId);
        }

        return $query->getQuery()->getResult();
    }

    /**
     * @param int[]|null $visibleProjectIds
     *
     * @return Sprint[]
     */
    public function findSprints(?int $projectId, ?array $visibleProjectIds = null): array
    {
        $query = $this->entityManager->createQueryBuilder()->select('s')->from(Sprint::class, 's')->orderBy('s.startAt', 'DESC');
        if (null !== $visibleProjectIds) {
            if ([] === $visibleProjectIds) {
                return [];
            }
            $query->andWhere('s.projectId IN (:visibleProjectIds)')->setParameter('visibleProjectIds', $visibleProjectIds);
        }
        if (null !== $projectId) {
            $query->andWhere('s.projectId = :projectId')->setParameter('projectId', $projectId);
        }

        return $query->getQuery()->getResult();
    }
}
