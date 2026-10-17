<?php

declare(strict_types=1);

namespace App\Application\Auth\Security;

final readonly class ActivationLinkSigner
{
    public function __construct(private string $secret)
    {
    }

    public function createUrl(string $appUrl, string $userId, int $activationVersion, int $expires): string
    {
        $signature = $this->signature($userId, $activationVersion, $expires);

        return \sprintf(
            '%s/api/auth/activate?user=%s&version=%d&expires=%d&signature=%s',
            rtrim($appUrl, '/'),
            rawurlencode($userId),
            $activationVersion,
            $expires,
            rawurlencode($signature),
        );
    }

    public function isValid(string $userId, int $activationVersion, int $expires, string $signature): bool
    {
        return $expires >= time()
            && hash_equals($this->signature($userId, $activationVersion, $expires), $signature);
    }

    private function signature(string $userId, int $activationVersion, int $expires): string
    {
        return hash_hmac('sha256', $userId.':'.$activationVersion.':'.$expires, $this->secret);
    }
}
