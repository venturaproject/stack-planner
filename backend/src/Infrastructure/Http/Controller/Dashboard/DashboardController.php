<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Dashboard;

use App\Application\Dashboard\Query\GetDashboardQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/dashboard/', methods: ['GET'])]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class DashboardController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(): JsonResponse
    {
        return $this->json($this->queryBus->ask(new GetDashboardQuery()));
    }
}
