<?php

declare(strict_types=1);

namespace App\Domain\Customer\ValueObject;

use InvalidArgumentException;

final readonly class Email
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (false === filter_var($normalized, \FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid email address', $value));
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function domain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
