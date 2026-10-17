<?php

declare(strict_types=1);

namespace App\Domain\Settings\Entity;

use App\Domain\Settings\Event\SecuritySettingsUpdated;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;

/**
 * Single-row, application-wide security policy. No "list of settings" abstraction
 * on purpose — there's exactly one flag right now (require2fa); add fields here
 * directly if/when more show up, don't build a generic key-value store for a
 * problem that doesn't exist yet.
 */
class SecuritySettings implements HasDomainEvents
{
    use RecordsDomainEvents;
    public const string SINGLETON_ID = 'default';

    private string $id = self::SINGLETON_ID;
    private bool $require2fa = false;

    public function getId(): string
    {
        return $this->id;
    }

    public function isRequire2fa(): bool
    {
        return $this->require2fa;
    }

    public function setRequire2fa(bool $require2fa): void
    {
        if ($this->require2fa === $require2fa) {
            return;
        }

        $this->require2fa = $require2fa;
        $this->raise(new SecuritySettingsUpdated($require2fa));
    }
}
