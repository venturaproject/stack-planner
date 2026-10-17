<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Symfony;

use App\Application\Shared\Security\CurrentUserIdProviderInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class SymfonyCurrentUserIdProvider implements CurrentUserIdProviderInterface
{
    public function __construct(private readonly Security $security)
    {
    }

    public function currentUserId(): ?string
    {
        $user = $this->security->getUser();

        return $user instanceof SecurityUser ? $user->domainUser()->getId() : null;
    }
}
