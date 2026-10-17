<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entity;

use App\Domain\Auth\Event\UserCreated;
use App\Domain\Auth\Event\UserDeactivated;
use App\Domain\Auth\Event\UserPasswordChanged;
use App\Domain\Auth\Event\UserTotpDisabled;
use App\Domain\Auth\Event\UserTotpEnabled;
use App\Domain\Auth\Event\UserUpdated;
use App\Domain\Auth\Exception\TotpNotEnabledException;
use App\Domain\Auth\Exception\TotpSetupNotInitiatedException;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use InvalidArgumentException;
use LogicException;

class User implements HasDomainEvents
{
    use RecordsDomainEvents;

    private ?string $id = null;
    private string $email;
    private string $passwordHash;
    private int $activationVersion = 0;
    private string $firstName;
    private string $lastName;
    private ?string $avatarUrl = null;
    private bool $active = true;
    private string $preferredLanguage = 'es';
    /** Set once setup is initiated; only meaningful together with $totpEnabled — see initiateTotpSetup(). */
    private ?string $totpSecret = null;
    private bool $totpEnabled = false;
    /** @var string[] sha256 hashes — never store backup codes in plaintext */
    private array $totpBackupCodeHashes = [];
    /** @var Collection<int, Role> */
    private Collection $roles;
    /** @var Collection<int, Permission> */
    private Collection $permissions;
    private DateTimeImmutable $createdAt;
    private DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->permissions = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function requireId(): string
    {
        if (null === $this->id) {
            throw new LogicException('A persisted user is required.');
        }

        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(string $hash): void
    {
        $this->passwordHash = $hash;
    }

    /** Embedded in an outstanding password-reset link so a used/superseded one stops validating — see PasswordResetLinkSigner. */
    public function getActivationVersion(): int
    {
        return $this->activationVersion;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): void
    {
        $this->avatarUrl = $avatarUrl;
    }

    public function getPreferredLanguage(): string
    {
        return $this->preferredLanguage;
    }

    public function setPreferredLanguage(string $preferredLanguage): void
    {
        if (!\in_array($preferredLanguage, ['en', 'es'], true)) {
            throw new InvalidArgumentException(\sprintf('Unsupported language "%s"', $preferredLanguage));
        }

        $this->preferredLanguage = $preferredLanguage;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /** @return iterable<int, Role> */
    public function getRoleEntities(): iterable
    {
        return $this->roles;
    }

    public function addRole(Role $role): void
    {
        $this->roles->add($role);
    }

    /** @param iterable<int, Role> $roles */
    public function setRoles(iterable $roles): void
    {
        $this->roles = new ArrayCollection([...$roles]);
    }

    /** @return iterable<int, Permission> */
    public function getDirectPermissions(): iterable
    {
        return $this->permissions;
    }

    /** @param iterable<int, Permission> $permissions */
    public function setDirectPermissions(iterable $permissions): void
    {
        $this->permissions = new ArrayCollection([...$permissions]);
    }

    public function recordCreated(): void
    {
        $this->raise(new UserCreated($this->id, $this->email, $this->activationVersion));
    }

    public function recordUpdated(): void
    {
        $this->raise(new UserUpdated($this->requireId(), $this->email));
    }

    public function changeEmail(string $newEmail): void
    {
        $this->email = $newEmail;
    }

    public function changePassword(string $hashedPassword): void
    {
        $this->passwordHash = $hashedPassword;
        $this->raise(new UserPasswordChanged($this->requireId(), $this->email));
    }

    public function activate(string $hashedPassword, int $activationVersion): void
    {
        if ($this->activationVersion !== $activationVersion) {
            throw new LogicException('The activation link has already been used.');
        }

        $this->passwordHash = $hashedPassword;
        $this->active = true;
        ++$this->activationVersion;
    }

    /**
     * Same single-use-link guard as activate(): $expectedVersion must match the
     * counter's value at the moment the reset link was issued, and bumping it here
     * invalidates that link (and any other outstanding one) the instant it's used.
     */
    public function resetPassword(string $hashedPassword, int $expectedVersion): void
    {
        if ($this->activationVersion !== $expectedVersion) {
            throw new LogicException('The password reset link has already been used.');
        }

        $this->passwordHash = $hashedPassword;
        ++$this->activationVersion;
        $this->raise(new UserPasswordChanged($this->requireId(), $this->email));
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->raise(new UserDeactivated($this->requireId(), $this->email));
    }

    public function isTotpEnabled(): bool
    {
        return $this->totpEnabled;
    }

    public function getTotpSecret(): ?string
    {
        return $this->totpSecret;
    }

    /**
     * @return string[]
     */
    public function getTotpBackupCodeHashes(): array
    {
        return $this->totpBackupCodeHashes;
    }

    /**
     * Stores a freshly generated secret pending confirmation — not active until
     * confirmTotpSetup() verifies the user actually scanned it and can produce a
     * valid code. Calling this again before confirming just replaces the pending
     * secret (e.g. the user reloaded the QR page), which is fine.
     */
    public function initiateTotpSetup(string $secret): void
    {
        $this->totpSecret = $secret;
    }

    /**
     * @param string[] $backupCodeHashes sha256 hashes of the plaintext codes shown
     *                                   to the user exactly once by the caller
     */
    public function confirmTotpSetup(array $backupCodeHashes): void
    {
        if (null === $this->totpSecret) {
            throw new TotpSetupNotInitiatedException();
        }

        $this->totpEnabled = true;
        $this->totpBackupCodeHashes = $backupCodeHashes;
        $this->raise(new UserTotpEnabled($this->id, $this->email));
    }

    public function disableTotp(): void
    {
        if (!$this->totpEnabled) {
            throw new TotpNotEnabledException();
        }

        $this->totpSecret = null;
        $this->totpEnabled = false;
        $this->totpBackupCodeHashes = [];
        $this->raise(new UserTotpDisabled($this->id, $this->email));
    }

    /**
     * Backup codes are single-use: removes the matching hash if present.
     */
    public function consumeBackupCode(string $codeHash): bool
    {
        $index = array_search($codeHash, $this->totpBackupCodeHashes, true);
        if (false === $index) {
            return false;
        }

        unset($this->totpBackupCodeHashes[$index]);
        $this->totpBackupCodeHashes = array_values($this->totpBackupCodeHashes);

        return true;
    }
}
