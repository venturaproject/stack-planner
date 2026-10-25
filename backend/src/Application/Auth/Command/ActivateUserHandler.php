<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;

final class ActivateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $hasher,
    ) {
    }

    public function handle(ActivateUserCommand $cmd): void
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $user->activate($this->hasher->hash($user, $cmd->password), $cmd->activationVersion);
        $this->users->save($user);
    }
}
