<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\UserResponse;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UpdateProfileHandler
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function handle(UpdateProfileCommand $cmd): UserResponse
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $user->setFirstName($cmd->firstName);
        $user->setLastName($cmd->lastName);
        if (null !== $cmd->preferredLanguage) {
            $user->setPreferredLanguage($cmd->preferredLanguage);
        }
        $user->setUpdatedAt(new DateTimeImmutable());

        return UserResponse::from($this->users->save($user));
    }
}
