<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Settings;

use App\Application\Settings\Command\UpdateSecuritySettingsCommand;
use App\Application\Settings\Query\GetSecuritySettingsQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin/security-settings')]
#[IsGranted('ROLE_ADMIN')]
class SecuritySettingsController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('', methods: ['GET'])]
    public function get(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetSecuritySettingsQuery()));
    }

    #[Route('', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || !\array_key_exists('require2fa', $data)) {
            return $this->json(['message' => 'Validation failed'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json($this->commandBus->dispatch(new UpdateSecuritySettingsCommand((bool) $data['require2fa'])));
    }
}
