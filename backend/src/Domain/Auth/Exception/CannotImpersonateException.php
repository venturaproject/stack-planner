<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

class CannotImpersonateException extends BusinessRuleException
{
    public static function inactiveUser(): self
    {
        return new self('Cannot impersonate an inactive user');
    }

    public static function own(): self
    {
        return new self('Cannot impersonate yourself');
    }
}
