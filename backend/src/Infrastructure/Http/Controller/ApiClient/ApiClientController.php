<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\ApiClient;

use App\Application\ApiClient\Command\CreateApiClientCommand;
use App\Application\ApiClient\Command\RevokeApiClientCommand;
use App\Application\ApiClient\Query\ListApiClientsQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\Http\Validation\ReadsJsonBody;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/api-clients')]
#[IsGranted('ROLE_ADMIN')]
class ApiClientController extends AbstractController
{
    use ReadsJsonBody;

    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new ListApiClientsQuery()));
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $this->decodeJsonBody($request);
        $name = trim($this->stringField($data, 'name'));
        $scopes = $this->stringListField($data, 'scopes');

        if ('' === $name || [] === $scopes) {
            return $this->json(['message' => 'name and scopes are required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = $this->commandBus->dispatch(new CreateApiClientCommand(name: $name, scopes: $scopes));

        return $this->json($result, Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function revoke(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new RevokeApiClientCommand($id));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
