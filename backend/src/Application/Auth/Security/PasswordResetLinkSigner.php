<?php

declare(strict_types=1);

namespace App\Application\Auth\Security;

/**
 * Same stateless signed-link shape as ActivationLinkSigner (HMAC + expiry, no DB
 * table), reusing User::activationVersion as the single-use guard: resetting the
 * password bumps it (see User::resetPassword()), so an already-used or superseded
 * link stops validating without needing a table to revoke it from.
 *
 * A distinct 'reset:' prefix in the signed payload (vs ActivationLinkSigner's
 * unprefixed one) keeps the two link types from being interchangeable even though
 * they'd otherwise sign the same (userId, version, expires) shape with the same
 * secret — without it, a leaked activation link could double as a valid-looking
 * password-reset signature for the same user/version/expires combination.
 */
final readonly class PasswordResetLinkSigner
{
    public function __construct(private string $secret)
    {
    }

    public function createUrl(string $appUrl, string $userId, int $version, int $expires): string
    {
        $signature = $this->signature($userId, $version, $expires);

        return \sprintf(
            '%s/reset-password?user=%s&version=%d&expires=%d&signature=%s',
            rtrim($appUrl, '/'),
            rawurlencode($userId),
            $version,
            $expires,
            rawurlencode($signature),
        );
    }

    public function isValid(string $userId, int $version, int $expires, string $signature): bool
    {
        return $expires >= time()
            && hash_equals($this->signature($userId, $version, $expires), $signature);
    }

    private function signature(string $userId, int $version, int $expires): string
    {
        return hash_hmac('sha256', 'reset:'.$userId.':'.$version.':'.$expires, $this->secret);
    }
}
