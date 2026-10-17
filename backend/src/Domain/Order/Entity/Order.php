<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Customer\ValueObject\Address;
use App\Domain\Order\Event\OrderCancelled;
use App\Domain\Order\Event\OrderLineAdded;
use App\Domain\Order\Event\OrderPlaced;
use App\Domain\Order\Exception\EmptyOrderException;
use App\Domain\Order\Exception\OrderLineNotFoundException;
use App\Domain\Order\Exception\OrderNotModifiableException;
use App\Domain\Order\ValueObject\OrderStatus;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use LogicException;

class Order implements HasDomainEvents
{
    use RecordsDomainEvents;

    private ?int $id = null;
    private int $customerId;
    private OrderStatus $status;
    private DateTimeImmutable $createdAt;

    // Shipping address is an immutable snapshot copied from the Customer at draft-start
    // time (see Customer::getAddress()) — never a live reference, so a later change to
    // the customer's address never rewrites this order's history.
    private ?string $shippingAddressStreet = null;
    private ?string $shippingAddressCity = null;
    private ?string $shippingAddressPostalCode = null;
    private ?string $shippingAddressCountry = null;

    // The total is always derived from the current lines (see recalculateTotal()) and
    // is never set directly, so it can never drift out of sync with them. Null while
    // the order has no lines yet.
    private ?int $totalAmountMinorUnits = null;
    private ?string $totalCurrency = null;

    /** @var Collection<int, OrderLine> */
    private Collection $lines;

    public function __construct()
    {
        $this->lines = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public static function startDraft(int $customerId, ?Address $shippingAddress): self
    {
        $order = new self();
        $order->customerId = $customerId;
        $order->status = OrderStatus::Draft;
        if (null !== $shippingAddress) {
            $order->shippingAddressStreet = $shippingAddress->street;
            $order->shippingAddressCity = $shippingAddress->city;
            $order->shippingAddressPostalCode = $shippingAddress->postalCode;
            $order->shippingAddressCountry = $shippingAddress->country;
        }

        return $order;
    }

    /**
     * Takes the product's identity/details as primitives, not the Product aggregate
     * itself — aggregates reference each other by identity only (see also customerId
     * above), so the caller (Application layer) loads the Product and extracts what's
     * needed before calling this.
     */
    public function addLine(int $productId, Sku $productSku, string $productName, Money $unitPrice, int $quantity): void
    {
        if (OrderStatus::Draft !== $this->status) {
            throw new OrderNotModifiableException($this->status);
        }

        $line = OrderLine::create($productId, $productSku, $productName, $unitPrice, $quantity);
        $line->assignToOrder($this);
        $this->lines->add($line);
        $this->recalculateTotal();

        $this->raise(new OrderLineAdded($this->id, $productId, $quantity));
    }

    public function changeLineQuantity(int $lineId, int $quantity): void
    {
        if (OrderStatus::Draft !== $this->status) {
            throw new OrderNotModifiableException($this->status);
        }

        $this->findLine($lineId)->changeQuantity($quantity);
        $this->recalculateTotal();
    }

    public function removeLine(int $lineId): void
    {
        if (OrderStatus::Draft !== $this->status) {
            throw new OrderNotModifiableException($this->status);
        }

        $line = $this->findLine($lineId);
        $this->lines->removeElement($line);
        $this->recalculateTotal();
    }

    private function findLine(int $lineId): OrderLine
    {
        foreach ($this->lines as $line) {
            if ($line->getId() === $lineId) {
                return $line;
            }
        }

        throw new OrderLineNotFoundException();
    }

    public function place(): void
    {
        if (OrderStatus::Draft !== $this->status) {
            throw new OrderNotModifiableException($this->status);
        }
        if ([] === [...$this->lines]) {
            throw new EmptyOrderException();
        }

        $this->status = OrderStatus::Placed;

        $total = $this->getTotal() ?? throw new EmptyOrderException();
        $this->raise(new OrderPlaced($this->id, $this->customerId, $total->amountMinorUnits(), $total->currency()->value));
    }

    public function cancel(): void
    {
        if (OrderStatus::Cancelled === $this->status) {
            throw new OrderNotModifiableException($this->status);
        }

        $this->status = OrderStatus::Cancelled;
        $this->raise(new OrderCancelled($this->id));
    }

    private function recalculateTotal(): void
    {
        $total = null;
        foreach ($this->lines as $line) {
            $total = null === $total ? $line->getLineTotal() : $total->add($line->getLineTotal());
        }

        $this->totalAmountMinorUnits = $total?->amountMinorUnits();
        $this->totalCurrency = $total?->currency()->value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        if (null === $this->id) {
            throw new LogicException('A persisted order is required.');
        }

        return $this->id;
    }

    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getShippingAddress(): ?Address
    {
        if (null === $this->shippingAddressStreet && null === $this->shippingAddressCity && null === $this->shippingAddressPostalCode && null === $this->shippingAddressCountry) {
            return null;
        }

        if (null === $this->shippingAddressStreet || null === $this->shippingAddressCity || null === $this->shippingAddressPostalCode || null === $this->shippingAddressCountry) {
            throw new LogicException('Order shipping address is incomplete.');
        }

        return new Address(
            $this->shippingAddressStreet,
            $this->shippingAddressCity,
            $this->shippingAddressPostalCode,
            $this->shippingAddressCountry,
        );
    }

    public function getTotal(): ?Money
    {
        if (null === $this->totalAmountMinorUnits || null === $this->totalCurrency) {
            return null;
        }

        return Money::fromMinorUnits($this->totalAmountMinorUnits, Currency::from($this->totalCurrency));
    }

    /** @return OrderLine[] */
    public function getLines(): array
    {
        return [...$this->lines];
    }
}
