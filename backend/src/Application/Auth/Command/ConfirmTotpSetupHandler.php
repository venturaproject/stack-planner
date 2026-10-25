<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\DTO\TotpConfirmResponse;
use App\Application\Shared\Totp\TotpServiceInterface;
use App\Domain\Auth\Exception\InvalidTotpCodeException;
use App\Domain\Auth\Exception\TotpSetupNotInitiatedException;
use App\Domain\Auth\Exception\UserNotFoundException;
use App\Domain\Auth\Repository\UserRepositoryInterface;

final class ConfirmTotpSetupHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly TotpServiceInterface $totp,
    ) {
    }

    public function handle(ConfirmTotpSetupCommand $cmd): TotpConfirmResponse
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new UserNotFoundException();

        $secret = $user->getTotpSecret()
            ?? throw new TotpSetupNotInitiatedException();

        if (!$this->totp->verify($secret, $cmd->code)) {
            throw new InvalidTotpCodeException();
        }

        $backupCodes = $this->totp->generateBackupCodes();
        $user->confirmTotpSetup(array_map(
            static fn (string $code) => hash('sha256', $code),
            $backupCodes,
        ));
        $this->users->save($user);

        return new TotpConfirmResponse($backupCodes);
    }
}
