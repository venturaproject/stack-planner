<?php

declare(strict_types=1);

use App\Domain\Auth\Entity\User;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'refresh_tokens']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator']);

$metadata->mapField(['fieldName' => 'token', 'type' => 'text', 'unique' => true]);
$metadata->mapField(['fieldName' => 'expiresAt', 'type' => 'datetime_immutable', 'columnName' => 'expires_at']);
$metadata->mapField(['fieldName' => 'revoked', 'type' => 'boolean']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);

$metadata->mapManyToOne([
    'fieldName' => 'user',
    'targetEntity' => User::class,
    'joinColumns' => [['name' => 'user_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']],
]);
