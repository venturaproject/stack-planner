<?php

declare(strict_types=1);

namespace App\Domain\Issue\Exception;

use RuntimeException;

final class IssueNotFoundException extends RuntimeException
{
    public static function forId(int $id): self
    {
        return new self(\sprintf('Issue %d was not found.', $id));
    }
}
