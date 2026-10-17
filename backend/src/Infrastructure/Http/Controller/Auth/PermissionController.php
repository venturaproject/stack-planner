<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Auth;

use App\Application\Auth\Command\CreatePermissionCommand;
use App\Application\Auth\Command\DeletePermissionCommand;
use App\Application\Auth\Command\UpdatePermissionCommand;
use App\Application\Auth\Query\ListPermissionsQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\Http\Validation\ReadsJsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/permissions')]
#[IsGranted('ROLE_ADMIN')]
class PermissionController extends AbstractController
{
    use ReadsJsonBody;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListPermissionsQuery(
            page: max(1, (int) $request->query->get('page', 1)),
            size: max(1, min(100, (int) $request->query->get('size', 20))),
        )));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJsonBody($request);

        return $this->json($this->commandBus->dispatch(
            new CreatePermissionCommand($this->stringField($data, 'name'), $this->optionalStringField($data, 'description'))
        ), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PATCH'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = $this->decodeJsonBody($request);

        return $this->json($this->commandBus->dispatch(
            new UpdatePermissionCommand($id, $this->stringField($data, 'name'), $this->optionalStringField($data, 'description'))
        ));
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeletePermissionCommand($id));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
