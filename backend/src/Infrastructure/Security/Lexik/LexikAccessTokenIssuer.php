<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Lexik;

use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Domain\Auth\Entity\User;
use App\Infrastructure\Security\Symfony\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

final class LexikAccessTokenIssuer implements AccessTokenIssuerInterface
{
    public function __construct(private readonly JWTTokenManagerInterface $tokens)
    {
    }

    public function issue(User $user, array $claims = []): string
    {
        $securityUser = new SecurityUser($user);

        return [] === $claims
            ? $this->tokens->create($securityUser)
            : $this->tokens->createFromPayload($securityUser, $claims);
    }
}
