<?php

declare(strict_types=1);

namespace App\Application\Shared\Security;

interface CurrentUserIdProviderInterface
{
    public function currentUserId(): ?string;
}
