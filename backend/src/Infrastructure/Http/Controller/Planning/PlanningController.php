<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Planning;

use App\Application\Planning\Query\ListEpicsQuery;
use App\Application\Planning\Query\ListSprintsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\Security\ProjectAccessPolicy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class PlanningController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly ProjectAccessPolicy $projectAccess,
    ) {
    }

    #[Route('/epics', methods: ['GET'])]
    public function epics(Request $request): JsonResponse
    {
        $rawId = $request->query->get('projectId');
        $projectId = is_numeric($rawId) ? (int) $rawId : null;
        if (null !== $projectId) {
            $this->projectAccess->assertProjectAccess($projectId);
        }

        return $this->json($this->queryBus->ask(new ListEpicsQuery($projectId, $this->projectAccess->visibleProjectIds())));
    }

    #[Route('/sprints', methods: ['GET'])]
    public function sprints(Request $request): JsonResponse
    {
        $rawId = $request->query->get('projectId');
        $projectId = is_numeric($rawId) ? (int) $rawId : null;
        if (null !== $projectId) {
            $this->projectAccess->assertProjectAccess($projectId);
        }

        return $this->json($this->queryBus->ask(new ListSprintsQuery($projectId, $this->projectAccess->visibleProjectIds())));
    }
}
