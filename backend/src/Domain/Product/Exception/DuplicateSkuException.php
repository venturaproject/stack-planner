<?php

declare(strict_types=1);

namespace App\Domain\Product\Exception;

use App\Domain\Product\ValueObject\Sku;
use App\Domain\Shared\Exception\BusinessRuleException;

class DuplicateSkuException extends BusinessRuleException
{
    public function __construct(Sku $sku)
    {
        parent::__construct(\sprintf('A product with SKU "%s" already exists', $sku->value()));
    }
}
