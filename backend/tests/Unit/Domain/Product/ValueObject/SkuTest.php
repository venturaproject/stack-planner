<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Product\ValueObject;

use App\Domain\Product\ValueObject\Sku;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SkuTest extends TestCase
{
    public function testNormalizesToUppercase(): void
    {
        self::assertSame('TSH-0001', (new Sku('tsh-0001'))->value());
    }

    public function testRejectsMalformedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Sku('not a sku');
    }

    public function testEqualsComparesByValue(): void
    {
        self::assertTrue((new Sku('TSH-0001'))->equals(new Sku('tsh-0001')));
    }

    public function testCastsToStringAsItsValue(): void
    {
        self::assertSame('TSH-0001', (string) new Sku('TSH-0001'));
    }
}
