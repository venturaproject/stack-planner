<?php

declare(strict_types=1);

namespace App\Application\Shared\Bus;

interface QueryBusInterface
{
    public function ask(object $query): mixed;
}
