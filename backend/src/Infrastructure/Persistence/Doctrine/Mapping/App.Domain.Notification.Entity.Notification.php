<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
// DESC on created_at in the migration (Version20260821110000) for the "most recent
// first" listing query; Doctrine's Index model doesn't track column sort direction,
// so schema-diff compares only the column list below and leaves the DB index alone.
$metadata->setPrimaryTable([
    'name' => 'notifications',
    'indexes' => [
        'idx_notifications_user_created_at' => ['columns' => ['user_id', 'created_at']],
    ],
]);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_CUSTOM);
$metadata->setCustomGeneratorDefinition(['class' => 'Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator']);

$metadata->mapField(['fieldName' => 'title', 'type' => 'string', 'length' => 255]);
$metadata->mapField(['fieldName' => 'message', 'type' => 'text']);
$metadata->mapField(['fieldName' => 'type', 'type' => 'string', 'length' => 50]);
$metadata->mapField(['fieldName' => 'readAt', 'type' => 'datetime_immutable', 'nullable' => true, 'columnName' => 'read_at']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
$metadata->mapField(['fieldName' => 'userId', 'type' => 'string', 'length' => 36, 'nullable' => true, 'columnName' => 'user_id']);
$metadata->mapField(['fieldName' => 'outboxEventId', 'type' => 'string', 'nullable' => true, 'length' => 36, 'unique' => true, 'columnName' => 'outbox_event_id']);
