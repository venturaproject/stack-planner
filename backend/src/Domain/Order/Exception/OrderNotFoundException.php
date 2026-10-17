<?php

declare(strict_types=1);

namespace App\Domain\Order\Exception;

use App\Domain\Shared\Exception\EntityNotFoundException;

class OrderNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('Order');
    }
}
