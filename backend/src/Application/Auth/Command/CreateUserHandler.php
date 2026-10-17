<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\UserResponse;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Exception\EmailAlreadyInUseException;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class CreateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly PasswordHasherInterface $hasher,
    ) {
    }

    public function handle(CreateUserCommand $cmd): UserResponse
    {
        if ($this->users->existsByEmail($cmd->email)) {
            throw new EmailAlreadyInUseException();
        }

        $user = new User();
        $user->setEmail($cmd->email);
        // New accounts cannot authenticate until their owner chooses a password.
        $user->setPasswordHash($this->hasher->hash($user, bin2hex(random_bytes(32))));
        $user->setActive(false);
        $user->setFirstName($cmd->firstName);
        $user->setLastName($cmd->lastName);

        if (null !== $cmd->roleIds) {
            $roles = [];
            foreach ($cmd->roleIds as $roleId) {
                $roles[] =
                    $this->roles->findById($roleId)
                        ?? throw new RoleNotFoundException();
            }
            $user->setRoles($roles);
        }

        $user->recordCreated();

        return UserResponse::from($this->users->save($user));
    }
}
