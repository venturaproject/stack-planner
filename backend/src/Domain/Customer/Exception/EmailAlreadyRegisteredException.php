<?php

declare(strict_types=1);

namespace App\Domain\Customer\Exception;

use App\Domain\Customer\ValueObject\Email;
use App\Domain\Shared\Exception\BusinessRuleException;

class EmailAlreadyRegisteredException extends BusinessRuleException
{
    public function __construct(Email $email)
    {
        parent::__construct(\sprintf('A customer with email "%s" is already registered', $email->value()));
    }
}
