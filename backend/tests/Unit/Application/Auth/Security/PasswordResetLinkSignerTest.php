<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Auth\Security;

use App\Application\Auth\Security\PasswordResetLinkSigner;
use PHPUnit\Framework\TestCase;

final class PasswordResetLinkSignerTest extends TestCase
{
    public function testCreatedUrlValidatesWithTheSameSigner(): void
    {
        $signer = new PasswordResetLinkSigner('secret');
        $expires = time() + 3600;
        $url = $signer->createUrl('https://app.example.com', 'user-1', 2, $expires);

        parse_str((string) parse_url($url, \PHP_URL_QUERY), $params);

        self::assertTrue($signer->isValid('user-1', 2, $expires, (string) $params['signature']));
    }

    public function testRejectsAnExpiredLink(): void
    {
        $signer = new PasswordResetLinkSigner('secret');
        $expired = time() - 1;
        $url = $signer->createUrl('https://app.example.com', 'user-1', 2, $expired);
        parse_str((string) parse_url($url, \PHP_URL_QUERY), $params);

        self::assertFalse($signer->isValid('user-1', 2, $expired, (string) $params['signature']));
    }

    public function testRejectsAMismatchedVersion(): void
    {
        $signer = new PasswordResetLinkSigner('secret');
        $expires = time() + 3600;
        $url = $signer->createUrl('https://app.example.com', 'user-1', 2, $expires);
        parse_str((string) parse_url($url, \PHP_URL_QUERY), $params);

        self::assertFalse($signer->isValid('user-1', 3, $expires, (string) $params['signature']));
    }

    public function testAnActivationLinkSignatureIsNotAlsoAValidResetSignature(): void
    {
        // Same (userId, version, expires) shape and secret as ActivationLinkSigner would
        // sign — the 'reset:' prefix in PasswordResetLinkSigner's own HMAC payload is
        // what stops the two link types being interchangeable.
        $expires = time() + 3600;
        $activationLikeSignature = hash_hmac('sha256', 'user-1:2:'.$expires, 'secret');

        $signer = new PasswordResetLinkSigner('secret');

        self::assertFalse($signer->isValid('user-1', 2, $expires, $activationLikeSignature));
    }
}
