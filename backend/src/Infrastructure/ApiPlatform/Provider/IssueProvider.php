<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Provider;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Application\Issue\Query\GetIssueDetailQuery;
use App\Application\Issue\Query\ListIssuesQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\ApiPlatform\Pagination\ApiPaginator;
use App\Infrastructure\ApiPlatform\Resource\IssueResource;
use App\Infrastructure\ApiPlatform\UriVariable;

/** @implements ProviderInterface<IssueResource|ApiPaginator<IssueResource>> */
final class IssueProvider implements ProviderInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array
    {
        if ($operation instanceof GetCollection) {
            $filters = $context['filters'] ?? [];
            $rawProjectId = \is_array($filters) ? ($filters['projectId'] ?? null) : null;
            $projectId = is_numeric($rawProjectId) ? (int) $rawProjectId : null;

            /** @var array{data: list<array<string, mixed>>} $result */
            $result = $this->queryBus->ask(new ListIssuesQuery($projectId));

            $items = array_map(IssueResource::fromArray(...), $result['data']);

            return new ApiPaginator($items, 1, max(1, \count($items)), 1, \count($items));
        }

        /** @var array<string, mixed> $result */
        $result = $this->queryBus->ask(new GetIssueDetailQuery(UriVariable::int($uriVariables, 'id')));

        return IssueResource::fromArray($result);
    }
}
