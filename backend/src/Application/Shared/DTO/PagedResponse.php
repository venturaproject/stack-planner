<?php

declare(strict_types=1);

namespace App\Application\Shared\DTO;

/**
 * @template T
 */
final readonly class PagedResponse
{
    /**
     * @param T[] $data
     */
    public function __construct(
        public array $data,
        public int $page,
        public int $size,
        public int $totalElements,
        public int $totalPages,
        public mixed $stats = null,
    ) {
    }

    /**
     * @template TIn
     * @template TOut
     *
     * @param TIn[]               $items
     * @param callable(TIn): TOut $mapper
     *
     * @return self<TOut>
     */
    public static function of(array $items, int $page, int $size, int $total, callable $mapper, mixed $stats = null): self
    {
        return new self(
            data: array_map($mapper, $items),
            page: $page,
            size: $size,
            totalElements: $total,
            totalPages: (int) ceil($total / max($size, 1)),
            stats: $stats,
        );
    }
}
