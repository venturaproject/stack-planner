<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class TaskStatusInput
{
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['todo', 'in_progress', 'in_review', 'done'])]
    public ?string $status = null;
}
