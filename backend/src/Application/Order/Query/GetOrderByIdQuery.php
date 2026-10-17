<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

final readonly class GetOrderByIdQuery
{
    public function __construct(public int $id)
    {
    }
}
