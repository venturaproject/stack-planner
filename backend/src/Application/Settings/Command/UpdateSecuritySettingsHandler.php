<?php

declare(strict_types=1);

namespace App\Application\Settings\Command;

use App\Application\Settings\DTO\SecuritySettingsResponse;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;

final class UpdateSecuritySettingsHandler
{
    public function __construct(
        private readonly SecuritySettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(UpdateSecuritySettingsCommand $cmd): SecuritySettingsResponse
    {
        $settings = $this->settings->findSingleton() ?? new SecuritySettings();
        $settings->setRequire2fa($cmd->require2fa);
        $settings = $this->settings->save($settings);

        return SecuritySettingsResponse::from($settings);
    }
}
