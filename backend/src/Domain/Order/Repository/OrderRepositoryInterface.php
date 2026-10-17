<?php

declare(strict_types=1);

namespace App\Domain\Order\Repository;

use App\Domain\Order\Entity\Order;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function findByIdForUpdate(int $id): ?Order;

    public function save(Order $order): Order;
}
