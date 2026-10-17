<?php

declare(strict_types=1);

use App\Domain\Auth\Entity\Permission;
use App\Domain\Auth\Entity\Role;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'users']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator']);

$metadata->mapField(['fieldName' => 'email', 'type' => 'string', 'length' => 255, 'unique' => true]);
$metadata->mapField(['fieldName' => 'passwordHash', 'type' => 'string', 'length' => 255, 'columnName' => 'password_hash']);
$metadata->mapField(['fieldName' => 'activationVersion', 'type' => 'integer', 'columnName' => 'activation_version']);
$metadata->mapField(['fieldName' => 'firstName', 'type' => 'string', 'length' => 100, 'columnName' => 'first_name']);
$metadata->mapField(['fieldName' => 'lastName', 'type' => 'string', 'length' => 100, 'columnName' => 'last_name']);
$metadata->mapField(['fieldName' => 'avatarUrl', 'type' => 'string', 'length' => 500, 'nullable' => true, 'columnName' => 'avatar_url']);
$metadata->mapField(['fieldName' => 'active', 'type' => 'boolean']);
$metadata->mapField(['fieldName' => 'preferredLanguage', 'type' => 'string', 'length' => 2, 'columnName' => 'preferred_language']);
$metadata->mapField(['fieldName' => 'totpSecret', 'type' => 'string', 'length' => 255, 'nullable' => true, 'columnName' => 'totp_secret']);
$metadata->mapField(['fieldName' => 'totpEnabled', 'type' => 'boolean', 'columnName' => 'totp_enabled']);
$metadata->mapField(['fieldName' => 'totpBackupCodeHashes', 'type' => 'json', 'columnName' => 'totp_backup_code_hashes']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
$metadata->mapField(['fieldName' => 'updatedAt', 'type' => 'datetime_immutable', 'columnName' => 'updated_at']);

$metadata->mapManyToMany([
    'fieldName' => 'roles',
    'targetEntity' => Role::class,
    'fetch' => ClassMetadata::FETCH_EAGER,
    'joinTable' => [
        'name' => 'user_roles',
        'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id']],
        'inverseJoinColumns' => [['name' => 'role_id', 'referencedColumnName' => 'id']],
    ],
]);

$metadata->mapManyToMany([
    'fieldName' => 'permissions',
    'targetEntity' => Permission::class,
    'fetch' => ClassMetadata::FETCH_EAGER,
    'joinTable' => [
        'name' => 'user_permissions',
        'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id']],
        'inverseJoinColumns' => [['name' => 'permission_id', 'referencedColumnName' => 'id']],
    ],
]);
