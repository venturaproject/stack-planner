<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'permissions']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator']);

$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 100, 'unique' => true]);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text', 'nullable' => true]);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable']);
