<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

use App\Domain\Shared\Exception\EntityNotFoundException;

class ProductNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('Product');
    }
}
