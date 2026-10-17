<?php

declare(strict_types=1);

namespace App\Application\Order\DTO;

use App\Domain\Order\Entity\Order;
use DateTimeInterface;

final readonly class OrderResponse
{
    /** @param OrderLineResponse[] $lines */
    public function __construct(
        public int $id,
        public int $customerId,
        public string $status,
        public ?string $shippingAddressStreet,
        public ?string $shippingAddressCity,
        public ?string $shippingAddressPostalCode,
        public ?string $shippingAddressCountry,
        public ?int $totalAmountMinorUnits,
        public ?string $totalCurrency,
        public array $lines,
        public string $createdAt,
    ) {
    }

    public static function from(Order $order): self
    {
        $address = $order->getShippingAddress();
        $total = $order->getTotal();

        return new self(
            id: $order->requireId(),
            customerId: $order->getCustomerId(),
            status: $order->getStatus()->value,
            shippingAddressStreet: $address?->street,
            shippingAddressCity: $address?->city,
            shippingAddressPostalCode: $address?->postalCode,
            shippingAddressCountry: $address?->country,
            totalAmountMinorUnits: $total?->amountMinorUnits(),
            totalCurrency: $total?->currency()->value,
            lines: array_map(OrderLineResponse::from(...), $order->getLines()),
            createdAt: $order->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
