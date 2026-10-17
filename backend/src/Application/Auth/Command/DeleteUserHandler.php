<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class DeleteUserHandler
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function handle(DeleteUserCommand $cmd): void
    {
        $this->users->delete(
            $this->users->findById($cmd->id) ?? throw new UserNotFoundException()
        );
    }
}
