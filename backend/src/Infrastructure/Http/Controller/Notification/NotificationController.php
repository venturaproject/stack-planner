<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Notification;

use App\Application\Notification\Command\DeleteAllNotificationsCommand;
use App\Application\Notification\Command\DeleteNotificationCommand;
use App\Application\Notification\Command\MarkAllReadCommand;
use App\Application\Notification\Command\MarkReadCommand;
use App\Application\Notification\Query\ListNotificationsQuery;
use App\Application\Shared\Bus\CommandBusInterface;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/notifications')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
    ) {
    }

    #[Route('/', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json(['notifications' => $this->queryBus->ask(new ListNotificationsQuery($this->userId()))]);
    }

    #[Route('/{id}/read', methods: ['PATCH'])]
    public function markRead(string $id): JsonResponse
    {
        return $this->json($this->commandBus->dispatch(new MarkReadCommand($id, $this->userId())));
    }

    #[Route('/read-all/', methods: ['PATCH'])]
    public function markAllRead(): JsonResponse
    {
        $this->commandBus->dispatch(new MarkAllReadCommand($this->userId()));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteNotificationCommand($id, $this->userId()));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/', methods: ['DELETE'])]
    public function deleteAll(): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteAllNotificationsCommand($this->userId()));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function userId(): string
    {
        /** @var SecurityUser $user */
        $user = $this->getUser();

        return $user->domainUser()->requireId();
    }
}
