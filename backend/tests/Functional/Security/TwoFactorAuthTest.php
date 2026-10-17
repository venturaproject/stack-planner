<?php

declare(strict_types=1);

namespace App\Tests\Functional\Security;

use App\Application\Shared\Totp\TotpServiceInterface;
use App\Domain\Settings\Entity\SecuritySettings;
use App\Domain\Settings\Repository\SecuritySettingsRepositoryInterface;
use App\Tests\Support\DatabaseWebTestCase;
use OTPHP\TOTP;

/**
 * End to end over cookies (see CsrfProtectionTest's docblock for why cookie-based,
 * not Authorization-header, login is what exercises the real enforcement path):
 * enable the require2fa policy, log in without TOTP set up (gets a scoped session),
 * confirm the enforcement listener blocks everything but /api/auth/totp/*, complete
 * setup, and confirm a normal session follows. Then a separate pass for a user who
 * already has TOTP enabled needing a code on every login.
 */
final class TwoFactorAuthTest extends DatabaseWebTestCase
{
    private function csrfHeader(): array
    {
        $token = $this->client->getCookieJar()->get('csrf_token')?->getValue();
        self::assertNotNull($token);

        return ['HTTP_X-CSRF-Token' => $token];
    }

    private function setRequire2fa(bool $enabled): void
    {
        $settings = self::getContainer()->get(SecuritySettingsRepositoryInterface::class)->findSingleton()
            ?? new SecuritySettings();
        $settings->setRequire2fa($enabled);
        self::getContainer()->get(SecuritySettingsRepositoryInterface::class)->save($settings);
    }

    private function loginViaCookies(string $email = 'policy-user@test.com', bool $asAdmin = false): void
    {
        $asAdmin ? $this->createAdmin($email) : $this->createUser($email);
        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => $email, 'password' => 'password'])
        );
    }

    public function testWithPolicyOffLoginIsUnaffected(): void
    {
        $this->setRequire2fa(false);
        $this->loginViaCookies();

        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/auth/me');
        self::assertResponseIsSuccessful();
    }

    public function testWithPolicyOnAnUnenrolledUserGetsAScopedSessionThatCannotReachOtherEndpoints(): void
    {
        $this->setRequire2fa(true);
        // Admin so a 403 here can only be attributed to the scoped-session listener,
        // not a plain ROLE_ADMIN check on /api/roles.
        $this->loginViaCookies(asAdmin: true);

        self::assertResponseIsSuccessful();

        $this->client->request('GET', '/api/roles');

        self::assertResponseStatusCodeSame(403);

        // /me stays reachable (it's exempt) specifically so the frontend can detect
        // this state proactively instead of discovering it via the 403 above.
        $this->client->request('GET', '/api/auth/me');
        self::assertResponseIsSuccessful();
        self::assertTrue($this->decodeResponse()['mustSetupTotp']);
    }

    public function testTheScopedSessionCanStillReachTotpSetupEndpoints(): void
    {
        $this->setRequire2fa(true);
        $this->loginViaCookies();
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/api/auth/totp/setup', [], [], $this->csrfHeader());

        self::assertResponseIsSuccessful();
        $data = $this->decodeResponse();
        self::assertNotEmpty($data['secret']);
        self::assertStringContainsString('otpauth://', $data['provisioningUri']);
    }

    public function testCompletingSetupUnlocksTheSessionImmediately(): void
    {
        $this->setRequire2fa(true);
        $this->loginViaCookies('completes-setup@test.com', asAdmin: true);
        self::assertResponseIsSuccessful();

        $this->client->request('POST', '/api/auth/totp/setup', [], [], $this->csrfHeader());
        $secret = $this->decodeResponse()['secret'];

        $code = $this->currentCodeFor($secret);

        $this->client->request(
            'POST',
            '/api/auth/totp/confirm',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'] + $this->csrfHeader(),
            $this->jsonBody(['code' => $code]),
        );
        self::assertResponseIsSuccessful();
        $backupCodes = $this->decodeResponse()['backupCodes'];
        self::assertCount(8, $backupCodes);

        // /totp/confirm reissues the access_token cookie without the stale
        // must_setup_totp claim, so the session is fully unlocked right away —
        // no second login required (the claim would otherwise never clear until
        // the JWT naturally expired, bouncing the user back to /totp-setup).
        $this->client->request('GET', '/api/roles');
        self::assertResponseIsSuccessful();
    }

    public function testAUserWithTotpAlreadyEnabledIsChallengedOnEveryLogin(): void
    {
        $user = $this->createUser('already-enrolled@test.com');
        $totp = self::getContainer()->get(TotpServiceInterface::class);
        $secret = $totp->generateSecret();
        $user->initiateTotpSetup($secret);
        $user->confirmTotpSetup([hash('sha256', 'BACKUP-CODE-1')]);
        $this->em->flush();

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'already-enrolled@test.com', 'password' => 'password']),
        );

        self::assertResponseStatusCodeSame(401);
        self::assertSame(['totpRequired' => true], $this->decodeResponse());

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'already-enrolled@test.com', 'password' => 'password', 'totpCode' => $this->currentCodeFor($secret)]),
        );
        self::assertResponseIsSuccessful();
    }

    public function testABackupCodeWorksInsteadOfATotpCode(): void
    {
        $user = $this->createUser('backup-code-user@test.com');
        $totp = self::getContainer()->get(TotpServiceInterface::class);
        $secret = $totp->generateSecret();
        $user->initiateTotpSetup($secret);
        $user->confirmTotpSetup([hash('sha256', 'MY-BACKUP-CODE')]);
        $this->em->flush();

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            $this->jsonBody(['email' => 'backup-code-user@test.com', 'password' => 'password', 'backupCode' => 'MY-BACKUP-CODE']),
        );

        self::assertResponseIsSuccessful();
    }

    private function currentCodeFor(string $secret): string
    {
        // Reach into otphp directly for a real code to submit — TotpServiceInterface
        // only exposes verify(), not code generation (nothing else needs it).
        return TOTP::createFromSecret($secret)->now();
    }
}
