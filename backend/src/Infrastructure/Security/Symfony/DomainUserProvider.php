<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Symfony;

use App\Domain\Auth\Repository\UserRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/** @implements UserProviderInterface<SecurityUser> */
final class DomainUserProvider implements UserProviderInterface
{
    public function __construct(private readonly UserRepositoryInterface $users)
    {
    }

    public function loadUserByIdentifier(string $identifier): SecurityUser
    {
        $user = $this->users->findByEmail($identifier);
        if (null === $user) {
            throw new UserNotFoundException();
        }

        return new SecurityUser($user);
    }

    public function refreshUser(UserInterface $user): SecurityUser
    {
        if (!$user instanceof SecurityUser) {
            throw new InvalidArgumentException('Unsupported user class.');
        }

        return $this->loadUserByIdentifier($user->getUserIdentifier());
    }

    public function supportsClass(string $class): bool
    {
        return SecurityUser::class === $class;
    }
}
