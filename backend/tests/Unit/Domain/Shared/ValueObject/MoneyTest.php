<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Shared\ValueObject;

use App\Domain\Shared\Exception\CurrencyMismatchException;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testAddSumsSameCurrencyAmounts(): void
    {
        $sum = Money::fromMinorUnits(1000, Currency::USD)->add(Money::fromMinorUnits(250, Currency::USD));

        self::assertSame(1250, $sum->amountMinorUnits());
        self::assertSame(Currency::USD, $sum->currency());
    }

    public function testAddThrowsOnCurrencyMismatch(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::fromMinorUnits(1000, Currency::USD)->add(Money::fromMinorUnits(1000, Currency::EUR));
    }

    public function testSubtractThrowsOnCurrencyMismatch(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::fromMinorUnits(1000, Currency::USD)->subtract(Money::fromMinorUnits(100, Currency::EUR));
    }

    public function testMultiplyScalesAmount(): void
    {
        $total = Money::fromMinorUnits(500, Currency::USD)->multiply(3);

        self::assertSame(1500, $total->amountMinorUnits());
    }

    public function testConstructionRejectsNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromMinorUnits(-1, Currency::USD);
    }

    public function testMultiplyRejectsNegativeFactor(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromMinorUnits(500, Currency::USD)->multiply(-1);
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue(
            Money::fromMinorUnits(500, Currency::USD)->equals(Money::fromMinorUnits(500, Currency::USD)),
        );
        self::assertFalse(
            Money::fromMinorUnits(500, Currency::USD)->equals(Money::fromMinorUnits(500, Currency::EUR)),
        );
    }

    public function testZeroCreatesAmountlessMoney(): void
    {
        self::assertSame(0, Money::zero(Currency::USD)->amountMinorUnits());
    }

    public function testSubtractDeductsSameCurrencyAmounts(): void
    {
        $diff = Money::fromMinorUnits(1000, Currency::USD)->subtract(Money::fromMinorUnits(400, Currency::USD));

        self::assertSame(600, $diff->amountMinorUnits());
    }

    public function testIsGreaterThanComparesAmounts(): void
    {
        self::assertTrue(Money::fromMinorUnits(500, Currency::USD)->isGreaterThan(Money::fromMinorUnits(100, Currency::USD)));
        self::assertFalse(Money::fromMinorUnits(100, Currency::USD)->isGreaterThan(Money::fromMinorUnits(500, Currency::USD)));
    }

    public function testIsGreaterThanThrowsOnCurrencyMismatch(): void
    {
        $this->expectException(CurrencyMismatchException::class);

        Money::fromMinorUnits(500, Currency::USD)->isGreaterThan(Money::fromMinorUnits(100, Currency::EUR));
    }
}
