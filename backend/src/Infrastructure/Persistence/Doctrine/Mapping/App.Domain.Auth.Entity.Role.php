<?php

declare(strict_types=1);

use App\Domain\Auth\Entity\Permission;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'roles']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator']);

$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 100, 'unique' => true]);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text', 'nullable' => true]);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable']);

$metadata->mapManyToMany([
    'fieldName' => 'permissions',
    'targetEntity' => Permission::class,
    'fetch' => ClassMetadata::FETCH_EAGER,
    'joinTable' => [
        'name' => 'role_permissions',
        'joinColumns' => [['name' => 'role_id', 'referencedColumnName' => 'id']],
        'inverseJoinColumns' => [['name' => 'permission_id', 'referencedColumnName' => 'id']],
    ],
]);
