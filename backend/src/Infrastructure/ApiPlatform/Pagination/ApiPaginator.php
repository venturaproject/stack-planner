<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Pagination;

use ApiPlatform\State\Pagination\PaginatorInterface;
use ArrayIterator;
use IteratorAggregate;

/**
 * @template T of object
 *
 * @implements IteratorAggregate<mixed, T>
 * @implements PaginatorInterface<T>
 */
final class ApiPaginator implements IteratorAggregate, PaginatorInterface
{
    /**
     * @param T[] $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $currentPage,
        private readonly int $itemsPerPage,
        private readonly int $lastPage,
        private readonly int $totalItems,
    ) {
    }

    /** @return ArrayIterator<int, T> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function getCurrentPage(): float
    {
        return (float) $this->currentPage;
    }

    public function getItemsPerPage(): float
    {
        return (float) $this->itemsPerPage;
    }

    public function getLastPage(): float
    {
        return (float) $this->lastPage;
    }

    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }
}
