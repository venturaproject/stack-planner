<?php

declare(strict_types=1);

namespace App\Domain\Project\Exception;

use App\Domain\Shared\Exception\BusinessRuleException;

final class CannotRemoveLastProjectOwnerException extends BusinessRuleException
{
    public function __construct()
    {
        parent::__construct('A project must keep at least one owner.');
    }
}
