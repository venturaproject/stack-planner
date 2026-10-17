<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Customer\ValueObject;

use App\Domain\Customer\ValueObject\Address;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testRejectsBlankFields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Address('', 'London', 'NW1', 'UK');
    }

    public function testEqualsComparesAllFields(): void
    {
        $a = new Address('221B Baker St', 'London', 'NW1', 'UK');
        $b = new Address('221B Baker St', 'London', 'NW1', 'UK');
        $c = new Address('10 Downing St', 'London', 'SW1A', 'UK');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
