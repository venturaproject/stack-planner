<?php

declare(strict_types=1);

namespace App\Domain\Product\ValueObject;

use InvalidArgumentException;

/**
 * Catalog SKU. Format is enforced at construction so a raw string can never
 * masquerade as a well-formed SKU: three letters/digits, a dash, then at
 * least four more letters/digits (e.g. "TSH-0042", "MUG-ABCD").
 */
final readonly class Sku
{
    private const PATTERN = '/^[A-Z0-9]{3}-[A-Z0-9]{4,}$/';

    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (1 !== preg_match(self::PATTERN, $normalized)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid SKU', $value));
        }

        $this->value = $normalized;
    }

    public function value(): string
    {
        return $this->value;
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
