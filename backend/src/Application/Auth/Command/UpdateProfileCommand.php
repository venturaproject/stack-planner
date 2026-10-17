<?php

declare(strict_types=1);

namespace App\Application\Auth\Command;

final class UpdateProfileCommand
{
    public string $userId = '';

    public string $firstName = '';

    public string $lastName = '';

    public ?string $preferredLanguage = null;
}
