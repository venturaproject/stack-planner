<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\TotpSetupResponse;
use App\Application\Shared\Totp\TotpServiceInterface;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class SetUpTotpHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TotpServiceInterface $totp,
    ) {
    }

    public function handle(SetUpTotpCommand $cmd): TotpSetupResponse
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $secret = $this->totp->generateSecret();
        $user->initiateTotpSetup($secret);
        $this->users->save($user);

        return new TotpSetupResponse(
            secret: $secret,
            provisioningUri: $this->totp->getProvisioningUri($secret, $user->getEmail()),
        );
    }
}
