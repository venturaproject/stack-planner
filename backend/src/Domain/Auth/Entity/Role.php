<?php

declare(strict_types=1);

namespace App\Domain\Auth\Entity;

use App\Domain\Auth\Event\RoleCreated;
use App\Domain\Auth\Event\RolePermissionsChanged;
use App\Domain\Shared\HasDomainEvents;
use App\Domain\Shared\RecordsDomainEvents;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use LogicException;

class Role implements HasDomainEvents
{
    use RecordsDomainEvents;

    private ?string $id = null;
    private string $name;
    private ?string $description = null;
    /** @var Collection<int, Permission> */
    private Collection $permissions;
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function requireId(): string
    {
        if (null === $this->id) {
            throw new LogicException('A persisted role is required.');
        }

        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /** @return iterable<int, Permission> */
    public function getPermissions(): iterable
    {
        return $this->permissions;
    }

    public function addPermission(Permission $permission): void
    {
        $this->permissions->add($permission);
    }

    /** @param iterable<int, Permission> $permissions */
    public function setPermissions(iterable $permissions): void
    {
        $this->permissions = new ArrayCollection([...$permissions]);
    }

    public function recordCreated(): void
    {
        $this->raise(new RoleCreated($this->id, $this->name));
    }

    public function recordPermissionsChanged(): void
    {
        $names = [];
        foreach ($this->permissions as $permission) {
            $names[] = $permission->getName();
        }
        $this->raise(new RolePermissionsChanged($this->requireId(), $this->name, $names));
    }
}
