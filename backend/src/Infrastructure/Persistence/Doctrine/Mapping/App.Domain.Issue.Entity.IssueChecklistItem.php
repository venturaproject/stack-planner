<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_issue_checklist_items', 'indexes' => ['idx_planner_checklist_issue' => ['columns' => ['issue_id', 'position']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'issueId', 'type' => 'integer', 'columnName' => 'issue_id']);
$metadata->mapField(['fieldName' => 'text', 'type' => 'string', 'length' => 500]);
$metadata->mapField(['fieldName' => 'isDone', 'type' => 'boolean', 'columnName' => 'is_done']);
$metadata->mapField(['fieldName' => 'position', 'type' => 'integer']);
