<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\AuthResponse;
use App\Application\Auth\DTO\UserResponse;
use App\Application\Auth\Security\AccessTokenIssuerInterface;
use App\Application\Shared\Event\DomainEventPublisherInterface;
use App\Domain\Auth\Event\UserImpersonated;
use App\Domain\Auth\Exception\CannotImpersonateException;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;

final class ImpersonateUserHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly AccessTokenIssuerInterface $tokens,
        private readonly DomainEventPublisherInterface $events,
    ) {
    }

    public function handle(ImpersonateUserCommand $cmd): AuthResponse
    {
        $target = $this->users->findById($cmd->targetUserId)
            ?? throw new UserNotFoundException();

        if (!$target->isActive()) {
            throw CannotImpersonateException::inactiveUser();
        }

        if (0 === strcasecmp($target->getEmail(), $cmd->impersonatorEmail)) {
            throw CannotImpersonateException::own();
        }

        // The extra claim is what StopImpersonatingHandler reads to hand control back
        // to the real admin — see AuthController::stopImpersonating().
        $accessToken = $this->tokens->issue($target, [
            'impersonator_email' => $cmd->impersonatorEmail,
        ]);

        $this->events->publish(new UserImpersonated(
            $target->requireId(),
            $target->getEmail(),
            $cmd->impersonatorEmail,
        ));

        return new AuthResponse(accessToken: $accessToken, user: UserResponse::from($target));
    }
}
