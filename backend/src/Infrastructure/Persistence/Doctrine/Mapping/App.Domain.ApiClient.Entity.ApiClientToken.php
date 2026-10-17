<?php

declare(strict_types=1);

use App\Domain\ApiClient\Entity\ApiClient;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'api_client_tokens']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField(['fieldName' => 'accessTokenHash', 'type' => 'string', 'length' => 64, 'unique' => true, 'columnName' => 'access_token_hash']);
$metadata->mapField(['fieldName' => 'refreshTokenHash', 'type' => 'string', 'length' => 64, 'unique' => true, 'columnName' => 'refresh_token_hash']);
$metadata->mapField(['fieldName' => 'scopes', 'type' => 'json']);
$metadata->mapField(['fieldName' => 'accessExpiresAt', 'type' => 'datetime_immutable', 'columnName' => 'access_expires_at']);
$metadata->mapField(['fieldName' => 'refreshExpiresAt', 'type' => 'datetime_immutable', 'columnName' => 'refresh_expires_at']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);

$metadata->mapManyToOne([
    'fieldName' => 'apiClient',
    'targetEntity' => ApiClient::class,
    'joinColumns' => [['name' => 'api_client_id', 'referencedColumnName' => 'id', 'nullable' => false, 'onDelete' => 'CASCADE']],
]);
