<?php

declare(strict_types=1);

namespace App\Application\Settings\DTO;

use App\Domain\Settings\Entity\SecuritySettings;

final readonly class SecuritySettingsResponse
{
    public function __construct(public bool $require2fa)
    {
    }

    public static function from(SecuritySettings $settings): self
    {
        return new self($settings->isRequire2fa());
    }
}
