<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

use App\Domain\Order\Entity\Order;
use App\Domain\Order\ValueObject\OrderStatus;
use DateTimeImmutable;

interface OrderReadModelInterface
{
    public function findById(int $id): ?Order;

    /** @param int[] $ids
     * @return Order[]
     */
    public function findByIds(array $ids): array;

    /** @return Order[] */
    public function findForExport(?int $customerId, ?OrderStatus $status, int $limit, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array;

    /** @return Order[] */
    public function findPaginated(int $page, int $limit, ?int $customerId, ?OrderStatus $status = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): array;

    public function countAll(?int $customerId, ?OrderStatus $status = null, ?DateTimeImmutable $createdFrom = null, ?DateTimeImmutable $createdTo = null): int;

    public function countByStatus(OrderStatus $status): int;
}
