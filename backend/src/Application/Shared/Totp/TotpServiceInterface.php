<?php

declare(strict_types=1);

namespace App\Application\Shared\Totp;

interface TotpServiceInterface
{
    /** A new base32 secret — not persisted or activated by this call. */
    public function generateSecret(): string;

    /** An otpauth:// URI suitable for rendering as a QR code (Google Authenticator, etc). */
    public function getProvisioningUri(string $secret, string $accountLabel): string;

    public function verify(string $secret, string $code): bool;

    /**
     * @return string[] plaintext codes — the caller must hash and store them, and
     *                  show these to the user exactly once
     */
    public function generateBackupCodes(int $count = 8): array;
}
