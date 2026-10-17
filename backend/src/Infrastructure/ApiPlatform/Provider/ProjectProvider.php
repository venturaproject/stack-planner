<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Project\Query\ListProjectsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\ApiPlatform\Pagination\ApiPaginator;
use App\Infrastructure\ApiPlatform\Resource\ProjectResource;

/** @implements ProviderInterface<ApiPaginator<ProjectResource>> */
final class ProjectProvider implements ProviderInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ApiPaginator
    {
        /** @var array{data: list<array<string, mixed>>} $result */
        $result = $this->queryBus->ask(new ListProjectsQuery());

        $items = array_map(ProjectResource::fromArray(...), $result['data']);

        return new ApiPaginator($items, 1, max(1, \count($items)), 1, \count($items));
    }
}
