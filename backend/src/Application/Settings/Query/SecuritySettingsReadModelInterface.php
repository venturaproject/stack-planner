<?php

declare(strict_types=1);

namespace App\Application\Settings\Query;

use App\Domain\Settings\Entity\SecuritySettings;

interface SecuritySettingsReadModelInterface
{
    public function findSingleton(): ?SecuritySettings;
}
