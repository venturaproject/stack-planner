<?php

declare(strict_types=1);

namespace App\Application\Planning\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListSprintsHandler
{
    public function __construct(private readonly PlanningReadModelInterface $planning)
    {
    }

    /** @return array{data: list<array<string, mixed>>} */
    public function handle(ListSprintsQuery $query): array
    {
        $data = [];
        foreach ($this->planning->findSprints($query->projectId) as $sprint) {
            $data[] = ['id' => $sprint->getId(), 'projectId' => $sprint->getProjectId(), 'name' => $sprint->getName(), 'goal' => $sprint->getGoal(), 'status' => $sprint->getStatus(), 'startAt' => $sprint->getStartAt()->format(\DATE_ATOM), 'endAt' => $sprint->getEndAt()->format(\DATE_ATOM)];
        }

        return ['data' => $data];
    }
}
