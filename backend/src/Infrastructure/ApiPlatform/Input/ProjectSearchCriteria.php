<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class ProjectSearchCriteria
{
    #[Assert\Length(max: 200)]
    public ?string $search = null;
}
