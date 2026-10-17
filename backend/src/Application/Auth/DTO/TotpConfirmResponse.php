<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

final readonly class TotpConfirmResponse
{
    /**
     * @param string[] $backupCodes plaintext — shown to the user exactly once, never
     *                              retrievable again (only sha256 hashes are stored)
     */
    public function __construct(public array $backupCodes)
    {
    }
}
