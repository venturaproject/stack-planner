<?php

declare(strict_types=1);

namespace App\Application\Auth\Security;

use App\Domain\Auth\Entity\User;

interface PasswordHasherInterface
{
    public function hash(User $user, string $plainPassword): string;

    public function verify(User $user, string $plainPassword): bool;
}
