<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Order\Entity;

use App\Domain\Customer\ValueObject\Address;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Event\OrderCancelled;
use App\Domain\Order\Event\OrderPlaced;
use App\Domain\Order\Exception\EmptyOrderException;
use App\Domain\Order\Exception\OrderLineNotFoundException;
use App\Domain\Order\Exception\OrderNotModifiableException;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use App\Tests\Support\EntityIdSetter;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    use EntityIdSetter;

    private function addLine(Order $order, string $sku = 'TSH-0001', int $priceMinorUnits = 1000, int $quantity = 1): void
    {
        $order->addLine(1, new Sku($sku), 'Product '.$sku, Money::fromMinorUnits($priceMinorUnits, Currency::USD), $quantity);
    }

    public function testStartDraftSnapshotsShippingAddress(): void
    {
        $address = new Address('221B Baker St', 'London', 'NW1', 'UK');
        $order = Order::startDraft(1, $address);

        self::assertTrue($address->equals($order->getShippingAddress()));
        self::assertSame(OrderStatus::Draft, $order->getStatus());
    }

    public function testAddLineAccumulatesTotal(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order, 'TSH-0001', 1000, 2);
        $this->addLine($order, 'MUG-0001', 500, 3);

        self::assertSame(3500, $order->getTotal()->amountMinorUnits());
        self::assertCount(2, $order->getLines());
    }

    public function testAddLineThrowsOnZeroOrNegativeQuantity(): void
    {
        $order = Order::startDraft(1, null);

        $this->expectException(InvalidArgumentException::class);

        $this->addLine($order, quantity: 0);
    }

    public function testPlaceRaisesOrderPlacedWithTotal(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order, 'TSH-0001', 1999, 2);
        $order->pullEvents();

        $order->place();

        self::assertSame(OrderStatus::Placed, $order->getStatus());
        $events = $order->pullEvents();
        self::assertInstanceOf(OrderPlaced::class, $events[0]);
        self::assertSame(3998, $events[0]->totalAmountMinorUnits);
    }

    public function testPlaceThrowsOnEmptyOrder(): void
    {
        $order = Order::startDraft(1, null);

        $this->expectException(EmptyOrderException::class);

        $order->place();
    }

    public function testCannotAddLineAfterOrderIsPlaced(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        $order->place();

        $this->expectException(OrderNotModifiableException::class);

        $this->addLine($order, 'MUG-0001');
    }

    public function testCannotPlaceAnAlreadyPlacedOrder(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        $order->place();

        $this->expectException(OrderNotModifiableException::class);

        $order->place();
    }

    public function testCancelFromDraftRaisesOrderCancelled(): void
    {
        $order = Order::startDraft(1, null);
        $order->pullEvents();

        $order->cancel();

        self::assertSame(OrderStatus::Cancelled, $order->getStatus());
        self::assertInstanceOf(OrderCancelled::class, $order->pullEvents()[0]);
    }

    public function testCancelFromPlacedIsAllowed(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        $order->place();

        $order->cancel();

        self::assertSame(OrderStatus::Cancelled, $order->getStatus());
    }

    public function testCannotCancelAnAlreadyCancelledOrder(): void
    {
        $order = Order::startDraft(1, null);
        $order->cancel();

        $this->expectException(OrderNotModifiableException::class);

        $order->cancel();
    }

    public function testChangeLineQuantityRecalculatesTotal(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order, 'TSH-0001', 1000, 2);
        self::setId($order->getLines()[0], 1);

        $order->changeLineQuantity(1, 5);

        self::assertSame(5, $order->getLines()[0]->getQuantity());
        self::assertSame(5000, $order->getTotal()->amountMinorUnits());
    }

    public function testChangeLineQuantityRejectsNonPositiveValue(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        self::setId($order->getLines()[0], 1);

        $this->expectException(InvalidArgumentException::class);

        $order->changeLineQuantity(1, 0);
    }

    public function testChangeLineQuantityThrowsWhenOrderNotDraft(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        self::setId($order->getLines()[0], 1);
        $order->place();

        $this->expectException(OrderNotModifiableException::class);

        $order->changeLineQuantity(1, 3);
    }

    public function testChangeLineQuantityThrowsWhenLineNotFound(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        self::setId($order->getLines()[0], 1);

        $this->expectException(OrderLineNotFoundException::class);

        $order->changeLineQuantity(999, 3);
    }

    public function testRemoveLineDropsItAndRecalculatesTotal(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order, 'TSH-0001', 1000, 2);
        $this->addLine($order, 'MUG-0001', 500, 1);
        self::setId($order->getLines()[0], 1);
        self::setId($order->getLines()[1], 2);

        $order->removeLine(1);

        self::assertCount(1, $order->getLines());
        self::assertSame(500, $order->getTotal()->amountMinorUnits());
    }

    public function testRemoveLineThrowsWhenOrderNotDraft(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        self::setId($order->getLines()[0], 1);
        $order->cancel();

        $this->expectException(OrderNotModifiableException::class);

        $order->removeLine(1);
    }

    public function testRemoveLineThrowsWhenLineNotFound(): void
    {
        $order = Order::startDraft(1, null);
        $this->addLine($order);
        self::setId($order->getLines()[0], 1);

        $this->expectException(OrderLineNotFoundException::class);

        $order->removeLine(999);
    }
}
