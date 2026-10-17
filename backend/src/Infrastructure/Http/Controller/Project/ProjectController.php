<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Project;

use App\Application\Project\Command\CreateProjectCommand;
use App\Application\Project\Query\ListProjectsQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\Security\ProjectAccessPolicy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/projects')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly ProjectAccessPolicy $projectAccess,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListProjectsQuery($this->projectAccess->memberUserIdOrNullForProjectList())));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateProjectCommand $command): JsonResponse
    {
        return $this->json($this->commandBus->dispatch($command), Response::HTTP_CREATED);
    }
}
