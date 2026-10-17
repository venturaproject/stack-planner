<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class LogoutHandler
{
    public function __construct(
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
        private readonly UserRepositoryInterface $users,
    ) {
    }

    public function handle(LogoutCommand $cmd): void
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $this->refreshTokens->revokeAllForUser($user);
    }
}
