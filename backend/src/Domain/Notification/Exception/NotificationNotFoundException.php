<?php

declare(strict_types=1);

namespace App\Domain\Notification\Exception;

use App\Domain\Shared\Exception\EntityNotFoundException;

class NotificationNotFoundException extends EntityNotFoundException
{
    public function __construct()
    {
        parent::__construct('Notification');
    }
}
