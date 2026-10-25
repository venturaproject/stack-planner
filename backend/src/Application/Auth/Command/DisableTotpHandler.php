<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

use App\Application\Auth\Exception\InvalidCredentialsException;
use App\Application\Auth\Security\PasswordHasherInterface;
use App\Domain\Auth\Exception\TotpRequiredByPolicyException;
use App\Domain\Auth\Repository\UserRepositoryInterface;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;

final class DisableTotpHandler
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly PasswordHasherInterface $hasher,
        private readonly SecuritySettingsRepositoryInterface $securitySettings,
    ) {
    }

    public function handle(DisableTotpCommand $cmd): void
    {
        $user = $this->users->findById($cmd->userId)
            ?? throw new InvalidCredentialsException();

        if (!$this->hasher->verify($user, $cmd->password)) {
            throw new InvalidCredentialsException();
        }

        // 2FA is mandatory system-wide right now — disabling would just force the
        // "set up TOTP" challenge again on the very next login. Not a security hole,
        // but confusing UX, so block it outright while the policy is on.
        if (($this->securitySettings->findSingleton() ?? new SecuritySettings())->isRequire2fa()) {
            throw new TotpRequiredByPolicyException();
        }

        $user->disableTotp();
        $this->users->save($user);
    }
}
