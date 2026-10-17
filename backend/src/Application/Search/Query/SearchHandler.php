<?php

declare(strict_types=1);

namespace App\Application\Search\Query;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class SearchHandler
{
    public function __construct(private readonly SearchReadModelInterface $search)
    {
    }

    /** @return array{users: list<array{id: string, name: string, email: string}>, roles: list<array{id: string, name: string}>, permissions: list<array{id: string, name: string, guard_name: string}>, categories: list<never>, tags: list<never>} */
    public function handle(SearchQuery $query): array
    {
        $term = trim($query->term);
        if ('' === $term) {
            return ['users' => [], 'roles' => [], 'permissions' => [], 'categories' => [], 'tags' => []];
        }

        return $this->search->search($term);
    }
}
