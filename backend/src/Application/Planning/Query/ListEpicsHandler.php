<?php

declare(strict_types=1);

namespace App\Application\Planning\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListEpicsHandler
{
    public function __construct(private readonly PlanningReadModelInterface $planning)
    {
    }

    /** @return array{data: list<array<string, mixed>>} */
    public function handle(ListEpicsQuery $query): array
    {
        $data = [];
        foreach ($this->planning->findEpics($query->projectId) as $epic) {
            $data[] = ['id' => $epic->getId(), 'projectId' => $epic->getProjectId(), 'key' => $epic->getEpicKey(), 'name' => $epic->getName(), 'description' => $epic->getDescription(), 'status' => $epic->getStatus(), 'progress' => $epic->getProgress()];
        }

        return ['data' => $data];
    }
}
