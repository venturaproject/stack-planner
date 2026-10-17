<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

final readonly class GetCustomerByIdQuery
{
    public function __construct(public int $id)
    {
    }
}
