<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Application\Issue\Query\ListIssuesQuery;
use App\Application\Shared\Bus\QueryBusInterface;
use App\Infrastructure\ApiPlatform\Input\IssueSearchCriteria;
use App\Infrastructure\ApiPlatform\Resource\IssueResource;

/** @implements ProcessorInterface<IssueSearchCriteria, IssueResource[]> */
final class IssueSearchProcessor implements ProcessorInterface
{
    public function __construct(private readonly QueryBusInterface $queryBus)
    {
    }

    /** @return IssueResource[] */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        /** @var IssueSearchCriteria $data */

        /** @var array{data: list<array<string, mixed>>} $result */
        $result = $this->queryBus->ask(new ListIssuesQuery($data->projectId));
        $search = mb_strtolower(trim($data->search ?? ''));
        $issues = array_filter($result['data'], static function (array $issue) use ($data, $search): bool {
            if (null !== $data->status && self::stringValue($issue, 'status') !== $data->status) {
                return false;
            }
            if (null !== $data->priority && self::stringValue($issue, 'priority') !== $data->priority) {
                return false;
            }
            if (null !== $data->type && self::stringValue($issue, 'type') !== $data->type) {
                return false;
            }
            if ('' === $search) {
                return true;
            }

            $assignees = $issue['assigneeNames'] ?? null;
            $assigneeNames = \is_array($assignees) ? implode(' ', array_filter($assignees, '\is_string')) : '';

            return str_contains(mb_strtolower(implode(' ', [
                self::stringValue($issue, 'key'),
                self::stringValue($issue, 'title'),
                self::stringValue($issue, 'description'),
                $assigneeNames,
            ])), $search);
        });

        return array_map(IssueResource::fromArray(...), array_values($issues));
    }

    /** @param array<string, mixed> $issue */
    private static function stringValue(array $issue, string $key): string
    {
        $value = $issue[$key] ?? null;

        return \is_string($value) ? $value : '';
    }
}
