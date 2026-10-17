<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Project\Query\ListProjectsQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\ApiPlatform\Input\ProjectSearchCriteria;
use App\Infrastructure\ApiPlatform\Resource\ProjectResource;

/** @implements ProcessorInterface<ProjectSearchCriteria, ProjectResource[]> */
final class ProjectSearchProcessor implements ProcessorInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /** @return ProjectResource[] */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var ProjectSearchCriteria $data */

        /** @var array{data: list<array<string, mixed>>} $result */
        $result = $this->queryBus->ask(new ListProjectsQuery());
        $search = mb_strtolower(trim($data->search ?? ''));
        $projects = '' === $search ? $result['data'] : array_filter(
            $result['data'],
            static fn (array $project): bool => str_contains(mb_strtolower(implode(' ', [
                self::stringValue($project, 'name'),
                self::stringValue($project, 'key'),
                self::stringValue($project, 'description'),
            ])), $search),
        );

        return array_map(ProjectResource::fromArray(...), array_values($projects));
    }

    /** @param array<string, mixed> $project */
    private static function stringValue(array $project, string $key): string
    {
        $value = $project[$key] ?? null;

        return \is_string($value) ? $value : '';
    }
}
