<?php

declare(strict_types=1);

namespace App\Domain\Customer\Exception;

use App\Domain\Shared\Exception\EntityNotFoundException;

class CustomerNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('Customer');
    }
}
