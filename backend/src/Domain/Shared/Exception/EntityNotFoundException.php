<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exception;

use RuntimeException;

class EntityNotFoundException extends RuntimeException
{
    public function __construct(string $entityName)
    {
        parent::__construct(\sprintf('%s not found', $entityName));
    }
}
