<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final class UpdateUserCommand
{
    public string $id = '';

    public string $firstName = '';

    public string $lastName = '';

    public ?string $email = null;

    public ?string $password = null;

    public bool $active = true;

    /** @var array<string>|null */
    public ?array $roleIds = null;

    /** @var array<string>|null */
    public ?array $permissionIds = null;
}
