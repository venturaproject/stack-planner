<?php

declare(strict_types=1);

namespace App\Domain\Product\Entity;

use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductPriceChanged;
use App\Domain\Product\Event\StockAdjusted;
use App\Domain\Product\Exception\InsufficientStockException;
use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;
use App\Domain\Shared\ValueObject\Currency;
use App\Domain\Shared\ValueObject\Money;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

class Product implements HasDomainEvents
{
    use RecordsDomainEvents;

    private ?int $id = null;

    // Sku is stored as a plain scalar column; the VO is reconstructed on read via getSku(),
    // same rationale as price below.
    private string $sku;

    private string $name;
    private ?string $description = null;

    // Money is stored as two scalar columns (see the Doctrine mapping file) rather than
    // through a Doctrine embeddable, so the VO is reconstructed on read via getPrice().
    private int $priceAmountMinorUnits;
    private string $priceCurrency;

    private int $stockQuantity;

    private ?string $imageFilename = null;

    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(Sku $sku, string $name, ?string $description, Money $price, int $initialStock): self
    {
        if ($initialStock < 0) {
            throw new InvalidArgumentException('Initial stock cannot be negative');
        }

        $product = new self();
        $product->sku = $sku->value();
        $product->name = $name;
        $product->description = $description;
        $product->setPrice($price);
        $product->stockQuantity = $initialStock;
        $product->raise(new ProductCreated($product->id, $sku->value(), $name));

        return $product;
    }

    public function updateDetails(?string $name, ?string $description): void
    {
        if (null !== $name) {
            $this->name = $name;
        }
        if (null !== $description) {
            $this->description = $description;
        }
    }

    public function changePrice(Money $newPrice): void
    {
        $previous = $this->getPrice();
        if ($newPrice->equals($previous)) {
            return;
        }

        $this->setPrice($newPrice);
        $this->raise(new ProductPriceChanged(
            $this->id,
            $previous->amountMinorUnits(),
            $newPrice->amountMinorUnits(),
            $newPrice->currency()->value,
        ));
    }

    private function setPrice(Money $price): void
    {
        $this->priceAmountMinorUnits = $price->amountMinorUnits();
        $this->priceCurrency = $price->currency()->value;
    }

    public function reserveStock(int $quantity, string $reason = 'order_placed'): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reserved quantity must be positive');
        }
        if ($quantity > $this->stockQuantity) {
            throw new InsufficientStockException($quantity, $this->stockQuantity);
        }

        $this->stockQuantity -= $quantity;
        $this->raise(new StockAdjusted($this->id, -$quantity, $this->stockQuantity, $reason));
    }

    public function restock(int $quantity, string $reason = 'restock'): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Restocked quantity must be positive');
        }

        $this->stockQuantity += $quantity;
        $this->raise(new StockAdjusted($this->id, $quantity, $this->stockQuantity, $reason));
    }

    public function attachImage(string $filename): void
    {
        $this->imageFilename = $filename;
    }

    public function removeImage(): void
    {
        $this->imageFilename = null;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        if (null === $this->id) {
            throw new LogicException('A persisted product is required.');
        }

        return $this->id;
    }

    public function getSku(): Sku
    {
        return new Sku($this->sku);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getPrice(): Money
    {
        return Money::fromMinorUnits($this->priceAmountMinorUnits, Currency::from($this->priceCurrency));
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
