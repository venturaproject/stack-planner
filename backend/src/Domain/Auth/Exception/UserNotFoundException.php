<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use App\Domain\Shared\Exception\EntityNotFoundException;

class UserNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('User');
    }
}
