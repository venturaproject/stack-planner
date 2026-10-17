<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ProductSearchCriteria
{
    #[Assert\Length(max: 200)]
    public ?string $search = null;

    #[Assert\Choice(choices: ['in_stock', 'low_stock', 'out_of_stock'])]
    public ?string $stockLevel = null;

    #[Assert\Date]
    public ?string $createdFrom = null;

    #[Assert\Date]
    public ?string $createdTo = null;

    #[Assert\Choice(choices: ['in_stock', 'out_of_stock'])]
    public ?string $stockStatus = null;

    #[Assert\PositiveOrZero]
    public ?int $priceMin = null;

    #[Assert\PositiveOrZero]
    public ?int $priceMax = null;

    #[Assert\Positive]
    public ?int $page = 1;

    #[Assert\Range(min: 1, max: 100)]
    public ?int $itemsPerPage = 20;
}
