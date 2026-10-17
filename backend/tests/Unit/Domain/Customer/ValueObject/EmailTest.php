<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Customer\ValueObject;

use App\Domain\Customer\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testNormalizesCaseAndWhitespace(): void
    {
        self::assertSame('ada@example.com', (new Email('  Ada@Example.com  '))->value());
    }

    public function testRejectsMalformedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Email('not-an-email');
    }

    public function testDomainReturnsPartAfterAt(): void
    {
        self::assertSame('example.com', (new Email('ada@example.com'))->domain());
    }

    public function testCastsToStringAsItsValue(): void
    {
        self::assertSame('ada@example.com', (string) new Email('ada@example.com'));
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue((new Email('ada@example.com'))->equals(new Email('ADA@Example.com')));
        self::assertFalse((new Email('ada@example.com'))->equals(new Email('grace@example.com')));
    }
}
