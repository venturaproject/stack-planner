<?php

declare(strict_types=1);

namespace App\Domain\Project\Exception;

use RuntimeException;

final class ProjectNotFoundException extends RuntimeException
{
    public static function forId(int $id): self
    {
        return new self(\sprintf('Project %d was not found.', $id));
    }
}
