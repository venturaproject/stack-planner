<?php

declare(strict_types=1);

namespace App\Application\Settings\Command;

final readonly class UpdateSecuritySettingsCommand
{
    public function __construct(public bool $require2fa)
    {
    }
}
