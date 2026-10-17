<?php

declare(strict_types=1);

namespace App\Domain\Settings\Event;

use App\Domain\Shared\DomainEvent;

final class SecuritySettingsUpdated extends DomainEvent
{
    public function __construct(public readonly bool $require2fa)
    {
        parent::__construct();
    }
}
