<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\UserResponse;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Exception\PermissionNotFoundException;
use App\Domain\Auth\Exception\RoleNotFoundException;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\PermissionRepositoryInterface;
use App\Domain\Auth\Repository\RefreshTokenRepositoryInterface;
use App\Domain\Auth\Repository\RoleRepositoryInterface;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class UpdateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly RoleRepositoryInterface $roles,
        private readonly PermissionRepositoryInterface $permissions,
        private readonly PasswordHasherInterface $hasher,
        private readonly RefreshTokenRepositoryInterface $refreshTokens,
    ) {
    }

    public function handle(UpdateUserCommand $cmd): UserResponse
    {
        $user = $this->users->findById($cmd->id)
            ?? throw new UserNotFoundException();

        $user->setFirstName($cmd->firstName);
        $user->setLastName($cmd->lastName);
        if (!$cmd->active && $user->isActive()) {
            $user->deactivate();
            $this->refreshTokens->revokeAllForUser($user);
        } else {
            $user->setActive($cmd->active);
        }

        if (null !== $cmd->email && '' !== $cmd->email) {
            $user->changeEmail($cmd->email);
        }

        if (null !== $cmd->password && '' !== $cmd->password) {
            $user->changePassword($this->hasher->hash($user, $cmd->password));
            $this->refreshTokens->revokeAllForUser($user);
        }
        $user->setUpdatedAt(new DateTimeImmutable());

        if (null !== $cmd->roleIds) {
            $roles = [];
            foreach ($cmd->roleIds as $roleId) {
                $roles[] =
                    $this->roles->findById($roleId)
                        ?? throw new RoleNotFoundException();
            }
            $user->setRoles($roles);
        }

        if (null !== $cmd->permissionIds) {
            $permissions = [];
            foreach ($cmd->permissionIds as $permId) {
                $permissions[] =
                    $this->permissions->findById($permId)
                        ?? throw new PermissionNotFoundException();
            }
            $user->setDirectPermissions($permissions);
        }

        $user->recordUpdated();

        return UserResponse::from($this->users->save($user));
    }
}
