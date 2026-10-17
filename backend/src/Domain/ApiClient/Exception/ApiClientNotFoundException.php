<?php

declare(strict_types=1);

namespace App\Domain\ApiClient\Exception;

use App\Domain\Shared\Exception\EntityNotFoundException;

class ApiClientNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('ApiClient');
    }
}
