<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller;

use App\Application\Search\Query\SearchQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/search', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
final class SearchController extends AbstractController
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return $this->json($this->queryBus->ask(new SearchQuery($request->query->getString('q'))));
    }
}
