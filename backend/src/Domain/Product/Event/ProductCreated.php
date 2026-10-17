<?php

declare(strict_types=1);

namespace App\Domain\Product\Event;

use App\Domain\Shared\DomainEvent;

final class ProductCreated extends DomainEvent
{
    public function __construct(
        public readonly ?int $productId,
        public readonly string $sku,
        public readonly string $name,
    ) {
        parent::__construct();
    }
}
