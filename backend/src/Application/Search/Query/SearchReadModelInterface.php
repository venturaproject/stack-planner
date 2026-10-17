<?php

declare(strict_types=1);

namespace App\Application\Search\Query;

interface SearchReadModelInterface
{
    /**
     * @return array{
     *     users: list<array{id: string, name: string, email: string}>,
     *     roles: list<array{id: string, name: string}>,
     *     permissions: list<array{id: string, name: string, guard_name: string}>,
     *     categories: list<never>,
     *     tags: list<never>
     * }
     */
    public function search(string $term): array;
}
