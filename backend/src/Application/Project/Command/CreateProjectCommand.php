<?php

declare(strict_types=1);

namespace App\Application\Project\Command;

final readonly class CreateProjectCommand
{
    public function __construct(
        public string $name,
        public string $key,
        public string $description = '',
    ) {
    }
}
