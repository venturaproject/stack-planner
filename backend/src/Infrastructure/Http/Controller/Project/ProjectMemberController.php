<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Project;

use App\Application\Project\Command\AddProjectMemberCommand;
use App\Application\Project\Command\RemoveProjectMemberCommand;
use App\Application\Project\Query\ListProjectMembersQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Domain\Project\Exception\ProjectNotFoundException;
use App\Domain\Project\Repository\ProjectRepositoryInterface;
use App\Infrastructure\Http\Validation\ReadsJsonBody;
use App\Infrastructure\Security\ProjectAccessPolicy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/projects/{projectId}/members')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class ProjectMemberController extends AbstractController
{
    use ReadsJsonBody;

    public function __construct(
        private readonly QueryBusInterface $queryBus,
        private readonly CommandBusInterface $commandBus,
        private readonly ProjectRepositoryInterface $projects,
        private readonly ProjectAccessPolicy $projectAccess,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(int $projectId): JsonResponse
    {
        $this->projectAccess->assertProjectAccess($projectId);
        if (null === $this->projects->findById($projectId)) {
            throw ProjectNotFoundException::forId($projectId);
        }

        return $this->json(['data' => $this->queryBus->ask(new ListProjectMembersQuery($projectId))]);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function add(int $projectId, Request $request): JsonResponse
    {
        $data = $this->decodeJsonBody($request);
        $userId = trim($this->stringField($data, 'userId'));
        $role = $this->stringField($data, 'role', 'member');
        if ('' === $userId) {
            return $this->json(['message' => 'userId is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->commandBus->dispatch(new AddProjectMemberCommand($projectId, $userId, $role)), Response::HTTP_CREATED);
    }

    #[Route('/{userId}', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(int $projectId, string $userId): JsonResponse
    {
        $this->commandBus->dispatch(new RemoveProjectMemberCommand($projectId, $userId));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
