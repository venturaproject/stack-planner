<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\CurrencyMismatchException;
use InvalidArgumentException;

/**
 * Amount stored as integer minor units (e.g. cents) to avoid float rounding
 * errors in arithmetic. Arithmetic operations only allow same-currency operands.
 */
final readonly class Money
{
    private function __construct(
        private int $amountMinorUnits,
        private Currency $currency,
    ) {
        if ($amountMinorUnits < 0) {
            throw new InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public static function fromMinorUnits(int $amountMinorUnits, Currency $currency): self
    {
        return new self($amountMinorUnits, $currency);
    }

    public static function zero(Currency $currency): self
    {
        return new self(0, $currency);
    }

    public function amountMinorUnits(): int
    {
        return $this->amountMinorUnits;
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinorUnits + $other->amountMinorUnits, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinorUnits - $other->amountMinorUnits, $this->currency);
    }

    public function multiply(int $factor): self
    {
        if ($factor < 0) {
            throw new InvalidArgumentException('Money multiplication factor cannot be negative');
        }

        return new self($this->amountMinorUnits * $factor, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->amountMinorUnits === $other->amountMinorUnits && $this->currency === $other->currency;
    }

    public function isGreaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amountMinorUnits > $other->amountMinorUnits;
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException($this->currency, $other->currency);
        }
    }
}
