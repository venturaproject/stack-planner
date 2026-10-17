<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Issue;

use App\Application\Issue\Command\CreateIssueCommand;
use App\Application\Issue\Command\UpdateIssueCommand;
use App\Application\Issue\Command\UpdateIssueStatusCommand;
use App\Application\Issue\Query\GetIssueDetailQuery;
use App\Application\Issue\Query\ListIssuesQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/issues')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class IssueController extends AbstractController
{
    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $rawProjectId = $request->query->get('projectId');
        $projectId = is_numeric($rawProjectId) ? (int) $rawProjectId : null;

        return $this->json($this->queryBus->ask(new ListIssuesQuery($projectId)));
    }

    #[Route('', methods: ['POST'])]
    public function create(#[MapRequestPayload] CreateIssueCommand $command): JsonResponse
    {
        return $this->json($this->commandBus->dispatch($command), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function detail(int $id): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetIssueDetailQuery($id)));
    }

    #[Route('/{id}/status', methods: ['PATCH'])]
    public function updateStatus(int $id, #[MapRequestPayload] UpdateIssueStatusCommand $command): JsonResponse
    {
        $command->id = $id;

        return $this->json($this->commandBus->dispatch($command));
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(int $id, #[MapRequestPayload] UpdateIssueCommand $command): JsonResponse
    {
        $command->id = $id;

        return $this->json($this->commandBus->dispatch($command));
    }
}
