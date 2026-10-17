<?php

declare(strict_types=1);

namespace App\Domain\Auth\Repository;

use App\Domain\Auth\Entity\RefreshToken;
use App\Domain\Auth\Entity\User;

interface RefreshTokenRepositoryInterface
{
    public function findByToken(string $token): ?RefreshToken;

    public function save(RefreshToken $token): RefreshToken;

    public function revokeAllForUser(User $user): void;
}
