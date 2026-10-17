<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

use App\Domain\Customer\Entity\Customer;
use DateTimeImmutable;

interface CustomerReadModelInterface
{
    public function findById(int $id): ?Customer;

    /** @param int[] $ids
     * @return Customer[]
     */
    public function findByIds(array $ids): array;

    /** @return Customer[] */
    public function findForExport(?string $search, ?bool $hasAddress, int $limit, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array;

    /** @return Customer[] */
    public function findPaginated(int $page, int $limit, ?string $search, ?bool $hasAddress = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array;

    public function countAll(?string $search, ?bool $hasAddress = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): int;

    public function countWithAddress(): int;
}
