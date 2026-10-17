<?php

declare(strict_types=1);

namespace App\Application\Issue\Command;

final class UpdateIssueStatusCommand
{
    public int $id = 0;

    public function __construct(public string $status)
    {
    }
}
