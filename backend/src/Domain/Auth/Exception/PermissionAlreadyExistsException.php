<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class PermissionAlreadyExistsException extends BusinessRuleException
{
    public function __construct(string $name)
    {
        parent::__construct(\sprintf("Permission '%s' already exists", $name));
    }
}
