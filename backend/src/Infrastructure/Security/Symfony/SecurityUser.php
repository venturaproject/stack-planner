<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Symfony;

use App\Domain\Auth\Entity\User;
use LogicException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    /** @var non-empty-string */
    private readonly string $identifier;

    public function __construct(private readonly User $user)
    {
        $identifier = $user->getEmail();
        if ('' === $identifier) {
            throw new LogicException('A security user must have an email address.');
        }

        $this->identifier = $identifier;
    }

    public function domainUser(): User
    {
        return $this->user;
    }

    public function getId(): ?string
    {
        return $this->user->getId();
    }

    public function getEmail(): string
    {
        return $this->identifier;
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getPassword(): string
    {
        return $this->user->getPasswordHash();
    }

    /** @return string[] */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        foreach ($this->user->getRoleEntities() as $role) {
            $roles[] = 'ROLE_'.$role->getName();
            foreach ($role->getPermissions() as $permission) {
                $roles[] = $permission->getName();
            }
        }
        foreach ($this->user->getDirectPermissions() as $permission) {
            $roles[] = $permission->getName();
        }

        return array_values(array_unique($roles));
    }

    public function eraseCredentials(): void
    {
    }
}
