<?php

declare(strict_types=1);

namespace App\Application\Product\Query;

use App\Domain\Product\Entity\Product;
use DateTimeImmutable;

interface ProductReadModelInterface
{
    public function findById(int $id): ?Product;

    /** @param int[] $ids
     * @return Product[]
     */
    public function findByIds(array $ids): array;

    /** @return Product[] */
    public function findForExport(?string $search, ?string $stockLevel, int $limit, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): array;

    /** @return Product[] */
    public function findPaginated(int $page, int $limit, ?string $search, ?string $stockLevel = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): array;

    public function countAll(?string $search, ?string $stockLevel = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null, ?string $stockStatus = null, ?int $priceMin = null, ?int $priceMax = null): int;

    public function countLowStock(int $threshold): int;
}
