<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Symfony;

use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class SymfonyPasswordHasher implements PasswordHasherInterface
{
    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function hash(User $user, string $plainPassword): string
    {
        return $this->hasher->hashPassword(new SecurityUser($user), $plainPassword);
    }

    public function verify(User $user, string $plainPassword): bool
    {
        return $this->hasher->isPasswordValid(new SecurityUser($user), $plainPassword);
    }
}
