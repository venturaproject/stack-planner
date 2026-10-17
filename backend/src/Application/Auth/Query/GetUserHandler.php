<?php

declare(strict_types=1);

namespace App\Application\Auth\Query;

use App\Application\Auth\DTO\UserResponse;
use App\Domain\Auth\Exception\UserNotFoundException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class GetUserHandler
{
    public function __construct(private readonly AuthReadModelInterface $users)
    {
    }

    public function handle(GetUserQuery $query): UserResponse
    {
        return UserResponse::from(
            $this->users->findUserById($query->id) ?? throw new UserNotFoundException()
        );
    }
}
