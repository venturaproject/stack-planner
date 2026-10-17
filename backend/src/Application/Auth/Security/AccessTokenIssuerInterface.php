<?php

declare(strict_types=1);

namespace App\Application\Auth\Security;

use App\Domain\Auth\Entity\User;

interface AccessTokenIssuerInterface
{
    /** @param array<string, mixed> $claims */
    public function issue(User $user, array $claims = []): string;
}
