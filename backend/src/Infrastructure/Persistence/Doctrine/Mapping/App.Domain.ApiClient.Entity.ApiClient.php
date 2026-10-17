<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'api_clients']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator']);

$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 255]);
$metadata->mapField(['fieldName' => 'clientId', 'type' => 'string', 'length' => 32, 'unique' => true, 'columnName' => 'client_id']);
$metadata->mapField(['fieldName' => 'secretHash', 'type' => 'string', 'length' => 64, 'columnName' => 'secret_hash']);
$metadata->mapField(['fieldName' => 'scopes', 'type' => 'json']);
$metadata->mapField(['fieldName' => 'active', 'type' => 'boolean']);
$metadata->mapField(['fieldName' => 'lastUsedAt', 'type' => 'datetime_immutable', 'nullable' => true, 'columnName' => 'last_used_at']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
