<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Auth\Entity;

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use App\Domain\Auth\Entity\User;
use App\Domain\Auth\Event\UserDeactivated;
use App\Domain\Auth\Event\UserPasswordChanged;
use App\Domain\Auth\Event\UserTotpDisabled;
use App\Domain\Auth\Event\UserTotpEnabled;
use App\Domain\Auth\Exception\TotpNotEnabledException;
use App\Domain\Auth\Exception\TotpSetupNotInitiatedException;
use App\Infrastructure\Security\Symfony\SecurityUser;
use App\Tests\Support\EntityIdSetter;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    use EntityIdSetter;

    private function user(): User
    {
        $user = new User();
        $user->setEmail('me@example.com');
        $user->setPasswordHash('hash');
        $user->setFirstName('Ada');
        $user->setLastName('Lovelace');

        return $user;
    }

    public function testSetPreferredLanguageRejectsUnsupportedValue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->user()->setPreferredLanguage('fr');
    }

    public function testGetRolesIncludesRolePermissionsAndDirectPermissions(): void
    {
        $user = $this->user();

        $rolePermission = new Permission();
        $rolePermission->setName('orders.view');
        $role = new Role();
        $role->setName('editor');
        $role->addPermission($rolePermission);
        $user->addRole($role);

        $directPermission = new Permission();
        $directPermission->setName('reports.export');
        $user->setDirectPermissions([$directPermission]);

        $roles = (new SecurityUser($user))->getRoles();

        self::assertContains('ROLE_USER', $roles);
        self::assertContains('ROLE_editor', $roles);
        self::assertContains('orders.view', $roles);
        self::assertContains('reports.export', $roles);
    }

    public function testDeactivateSetsInactiveAndRaisesEvent(): void
    {
        $user = $this->user();
        self::setId($user, 'user-1');
        $user->pullEvents();

        $user->deactivate();

        self::assertFalse($user->isActive());
        $events = $user->pullEvents();
        self::assertInstanceOf(UserDeactivated::class, $events[0]);
        self::assertSame('me@example.com', $events[0]->email);
    }

    public function testChangePasswordUpdatesHashAndRaisesEventWithoutThePlainPassword(): void
    {
        $user = $this->user();
        self::setId($user, 'user-1');
        $user->pullEvents();

        $user->changePassword('new-hash');

        self::assertSame('new-hash', $user->getPasswordHash());
        $events = $user->pullEvents();
        self::assertInstanceOf(UserPasswordChanged::class, $events[0]);
        self::assertSame('me@example.com', $events[0]->email);
        self::assertFalse(property_exists($events[0], 'plainPassword'));
    }

    public function testActivationSetsPasswordAndInvalidatesTheLink(): void
    {
        $user = $this->user();
        $user->setActive(false);

        $user->activate('new-hash', 0);

        self::assertSame('new-hash', $user->getPasswordHash());
        self::assertTrue($user->isActive());
        $this->expectException(LogicException::class);
        $user->activate('another-hash', 0);
    }

    public function testConfirmTotpSetupWithoutInitiatingFirstThrows(): void
    {
        $this->expectException(TotpSetupNotInitiatedException::class);

        $this->user()->confirmTotpSetup(['hash1']);
    }

    public function testConfirmTotpSetupEnablesTotpAndRaisesEvent(): void
    {
        $user = $this->user();
        self::setId($user, 'user-1');
        $user->initiateTotpSetup('SECRET123');
        $user->pullEvents();

        $user->confirmTotpSetup(['hash1', 'hash2']);

        self::assertTrue($user->isTotpEnabled());
        self::assertSame(['hash1', 'hash2'], $user->getTotpBackupCodeHashes());
        $events = $user->pullEvents();
        self::assertInstanceOf(UserTotpEnabled::class, $events[0]);
        self::assertSame('me@example.com', $events[0]->email);
    }

    public function testInitiateTotpSetupTwiceReplacesThePendingSecret(): void
    {
        $user = $this->user();
        $user->initiateTotpSetup('FIRST');
        $user->initiateTotpSetup('SECOND');

        self::assertSame('SECOND', $user->getTotpSecret());
    }

    public function testDisableTotpWhenNotEnabledThrows(): void
    {
        $this->expectException(TotpNotEnabledException::class);

        $this->user()->disableTotp();
    }

    public function testDisableTotpClearsStateAndRaisesEvent(): void
    {
        $user = $this->user();
        self::setId($user, 'user-1');
        $user->initiateTotpSetup('SECRET123');
        $user->confirmTotpSetup(['hash1']);
        $user->pullEvents();

        $user->disableTotp();

        self::assertFalse($user->isTotpEnabled());
        self::assertNull($user->getTotpSecret());
        self::assertSame([], $user->getTotpBackupCodeHashes());
        $events = $user->pullEvents();
        self::assertInstanceOf(UserTotpDisabled::class, $events[0]);
    }

    public function testConsumeBackupCodeRemovesAMatchingHashAndReturnsTrue(): void
    {
        $user = $this->user();
        $user->initiateTotpSetup('SECRET123');
        $user->confirmTotpSetup(['hash1', 'hash2']);

        self::assertTrue($user->consumeBackupCode('hash1'));
        self::assertSame(['hash2'], $user->getTotpBackupCodeHashes());
        // Single-use: consuming the same code again fails.
        self::assertFalse($user->consumeBackupCode('hash1'));
    }

    public function testConsumeBackupCodeReturnsFalseForAnUnknownHash(): void
    {
        $user = $this->user();
        $user->initiateTotpSetup('SECRET123');
        $user->confirmTotpSetup(['hash1']);

        self::assertFalse($user->consumeBackupCode('not-a-real-hash'));
        self::assertSame(['hash1'], $user->getTotpBackupCodeHashes());
    }
}
