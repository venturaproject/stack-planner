<?php

declare(strict_types=1);

namespace App\Domain\Order\Entity;

use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use InvalidArgumentException;
use LogicException;

/**
 * Child entity of the Order aggregate — has its own identity and DB row, unlike a
 * Value Object, because two lines with the same product+quantity are still distinct
 * (added independently, removable independently). Snapshots the product's sku, name
 * and price at add-time so later catalog changes never rewrite a placed order's history.
 */
class OrderLine
{
    private ?int $id = null;

    // Doctrine requires the many-to-one owning side for a mapped one-to-many
    // collection; this back-reference is ORM plumbing only, never read by domain
    // logic — Order is set once by Order::addLine() right after construction.
    private Order $order;

    private int $productId;
    private string $productSku;
    private string $productName;
    private int $unitPriceAmountMinorUnits;
    private string $unitPriceCurrency;
    private int $quantity;

    public static function create(int $productId, Sku $productSku, string $productName, Money $unitPrice, int $quantity): self
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Order line quantity must be positive');
        }

        $line = new self();
        $line->productId = $productId;
        $line->productSku = $productSku->value();
        $line->productName = $productName;
        $line->unitPriceAmountMinorUnits = $unitPrice->amountMinorUnits();
        $line->unitPriceCurrency = $unitPrice->currency()->value;
        $line->quantity = $quantity;

        return $line;
    }

    /** @internal set by Order::addLine() only — see the $order property comment. */
    public function assignToOrder(Order $order): void
    {
        $this->order = $order;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        if (null === $this->id) {
            throw new LogicException('A persisted order line is required.');
        }

        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getProductSku(): Sku
    {
        return new Sku($this->productSku);
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getUnitPrice(): Money
    {
        return Money::fromMinorUnits($this->unitPriceAmountMinorUnits, Currency::from($this->unitPriceCurrency));
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function changeQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Order line quantity must be positive');
        }

        $this->quantity = $quantity;
    }

    public function getLineTotal(): Money
    {
        return $this->getUnitPrice()->multiply($this->quantity);
    }
}
