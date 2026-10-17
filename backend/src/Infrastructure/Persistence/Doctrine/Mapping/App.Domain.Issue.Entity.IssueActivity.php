<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_issue_activities', 'indexes' => ['idx_planner_activity_issue' => ['columns' => ['issue_id', 'created_at']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'issueId', 'type' => 'integer', 'columnName' => 'issue_id']);
$metadata->mapField(['fieldName' => 'actorName', 'type' => 'string', 'length' => 180, 'columnName' => 'actor_name']);
$metadata->mapField(['fieldName' => 'field', 'type' => 'string', 'length' => 80]);
$metadata->mapField(['fieldName' => 'oldValue', 'type' => 'string', 'length' => 255, 'nullable' => true, 'columnName' => 'old_value']);
$metadata->mapField(['fieldName' => 'newValue', 'type' => 'string', 'length' => 255, 'columnName' => 'new_value']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
