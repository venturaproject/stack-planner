<?php

declare(strict_types=1);

namespace App\Infrastructure\ApiPlatform\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class IssueSearchCriteria
{
    #[Assert\Positive]
    public ?int $projectId = null;

    #[Assert\Length(max: 200)]
    public ?string $search = null;

    #[Assert\Choice(choices: ['todo', 'in_progress', 'in_review', 'done'])]
    public ?string $status = null;

    #[Assert\Choice(choices: ['low', 'medium', 'high', 'urgent', 'critical'])]
    public ?string $priority = null;

    #[Assert\Choice(choices: ['story', 'task', 'bug', 'subtask'])]
    public ?string $type = null;
}
