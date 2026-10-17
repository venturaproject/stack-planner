<?php

declare(strict_types=1);

use App\Domain\AuditLog\Entity\AuditLog;
use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata<AuditLog> $metadata */
$metadata->setPrimaryTable(['name' => 'audit_logs']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'string', 'id' => true, 'length' => 36]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);

$metadata->mapField(['fieldName' => 'actorId',      'type' => 'string',             'nullable' => true, 'length' => 36,  'columnName' => 'actor_id']);
$metadata->mapField(['fieldName' => 'actorEmail',   'type' => 'string',             'nullable' => true, 'length' => 255, 'columnName' => 'actor_email']);
$metadata->mapField(['fieldName' => 'action',       'type' => 'string',             'length' => 100]);
$metadata->mapField(['fieldName' => 'resourceType', 'type' => 'string',             'length' => 100,    'columnName' => 'resource_type']);
$metadata->mapField(['fieldName' => 'resourceId',   'type' => 'string',             'nullable' => true, 'length' => 255, 'columnName' => 'resource_id']);
$metadata->mapField(['fieldName' => 'metadata',     'type' => 'json']);
$metadata->mapField(['fieldName' => 'ipAddress',    'type' => 'string',             'nullable' => true, 'length' => 45,  'columnName' => 'ip_address']);
$metadata->mapField(['fieldName' => 'previousHash', 'type' => 'string',             'length' => 64,     'columnName' => 'previous_hash']);
$metadata->mapField(['fieldName' => 'hash',         'type' => 'string',             'length' => 64,     'unique' => true]);
$metadata->mapField(['fieldName' => 'createdAt',    'type' => 'datetime_immutable', 'columnName' => 'created_at']);
$metadata->mapField(['fieldName' => 'outboxEventId', 'type' => 'string', 'nullable' => true, 'length' => 36, 'unique' => true, 'columnName' => 'outbox_event_id']);

// Assigned by a real Postgres sequence (see the migration), not by application
// code — 'generated' => GENERATED_INSERT excludes it from the INSERT statement
// entirely, letting the column's own DEFAULT nextval(...) apply, and tells
// Doctrine to read the actual value back afterward. getLastHash() orders by
// this instead of createdAt/id — see DoctrineAuditLogRepository for why a
// UUIDv7's monotonicity isn't enough on its own.
$metadata->mapField([
    'fieldName' => 'chainSequence',
    'type' => 'bigint',
    'columnName' => 'chain_sequence',
    'generated' => ClassMetadata::GENERATED_INSERT,
    'notInsertable' => true,
    'unique' => true,
    // Spelled out so doctrine:migrations:diff sees the DB's real DEFAULT and
    // doesn't propose dropping it as drift — the migration is still what
    // actually creates the sequence and wires it to the column.
    'options' => ['default' => "nextval('audit_logs_chain_sequence_seq'::regclass)"],
]);
