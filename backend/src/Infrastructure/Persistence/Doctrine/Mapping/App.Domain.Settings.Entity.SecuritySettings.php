<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'security_settings']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 32]);
$metadata->mapField(['fieldName' => 'require2fa', 'type' => 'boolean', 'columnName' => 'require_2fa']);
