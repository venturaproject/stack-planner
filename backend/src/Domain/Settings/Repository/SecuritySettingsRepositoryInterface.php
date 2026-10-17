<?php

declare(strict_types=1);

namespace App\Domain\Settings\Repository;

use App\Domain\Settings\Entity\SecuritySettings;

interface SecuritySettingsRepositoryInterface
{
    public function findSingleton(): ?SecuritySettings;

    public function save(SecuritySettings $settings): SecuritySettings;
}
