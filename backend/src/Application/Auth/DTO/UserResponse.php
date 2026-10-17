<?php

declare(strict_types=1);

namespace App\Application\Auth\DTO;

use App\Domain\Auth\Entity\User;
use DateTimeInterface;

final readonly class UserResponse
{
    /**
     * @param RoleResponse[]       $roles
     * @param PermissionResponse[] $permissions
     */
    public function __construct(
        public string $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?string $avatarUrl,
        public bool $active,
        public string $preferredLanguage,
        public array $roles,
        public array $permissions,
        public string $createdAt,
        public string $updatedAt,
        public bool $totpEnabled = false,
    ) {
    }

    public static function from(User $user): self
    {
        return new self(
            id: $user->requireId(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            avatarUrl: $user->getAvatarUrl(),
            active: $user->isActive(),
            preferredLanguage: $user->getPreferredLanguage(),
            roles: array_map(
                static fn ($r) => RoleResponse::from($r),
                [...$user->getRoleEntities()]
            ),
            permissions: array_map(
                static fn ($p) => PermissionResponse::from($p),
                [...$user->getDirectPermissions()]
            ),
            createdAt: $user->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $user->getUpdatedAt()->format(DateTimeInterface::ATOM),
            totpEnabled: $user->isTotpEnabled(),
        );
    }
}
