<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;

final class ResetPasswordHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $hasher,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
    ) {
    }

    public function handle(ResetPasswordCommand $cmd): void
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $user->resetPassword($this->hasher->hash($user, $cmd->password), $cmd->version);
        $this->users->save($user);
        $this->refreshTokens->revokeAllForUser($user);
    }
}
