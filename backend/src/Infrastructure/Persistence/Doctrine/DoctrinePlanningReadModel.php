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

    public function findEpics(?int $projectId): array
    {
        $query = $this->entityManager->createQueryBuilder()->select('e')->from(Epic::class, 'e')->orderBy('e.epicKey', 'ASC');
        if (null !== $projectId) {
            $query->where('e.projectId = :projectId')->setParameter('projectId', $projectId);
        }

        return $query->getQuery()->getResult();
    }

    public function findSprints(?int $projectId): array
    {
        $query = $this->entityManager->createQueryBuilder()->select('s')->from(Sprint::class, 's')->orderBy('s.startAt', 'DESC');
        if (null !== $projectId) {
            $query->where('s.projectId = :projectId')->setParameter('projectId', $projectId);
        }

        return $query->getQuery()->getResult();
    }
}
