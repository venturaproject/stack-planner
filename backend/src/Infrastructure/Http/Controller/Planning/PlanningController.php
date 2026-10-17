<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Planning;

use App\Application\Planning\Query\ListEpicsQuery;
use App\Application\Planning\Query\ListSprintsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PlanningController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    #[Route('/epics', methods: ['GET'])]
    public function epics(Request $request): JsonResponse
    {
        $rawId = $request->query->get('projectId');

        return $this->json($this->queryBus->ask(new ListEpicsQuery(is_numeric($rawId) ? (int) $rawId : null)));
    }

    #[Route('/sprints', methods: ['GET'])]
    public function sprints(Request $request): JsonResponse
    {
        $rawId = $request->query->get('projectId');

        return $this->json($this->queryBus->ask(new ListSprintsQuery(is_numeric($rawId) ? (int) $rawId : null)));
    }
}
