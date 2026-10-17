<?php

declare(strict_types=1);

namespace App\Tests\Support;

use ReflectionProperty;

trait EntityIdSetter
{
    private static function setId(object $entity, int|string $id): void
    {
        (new ReflectionProperty($entity, 'id'))->setValue($entity, $id);
    }
}
