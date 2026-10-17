<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable([
    'name' => 'planner_issues',
    'uniqueConstraints' => ['uniq_planner_issue_key' => ['columns' => ['issue_key']]],
    'indexes' => ['idx_planner_issue_project_status' => ['columns' => ['project_id', 'status']]],
]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'projectId', 'type' => 'integer', 'columnName' => 'project_id']);
$metadata->mapField(['fieldName' => 'issueKey', 'type' => 'string', 'length' => 24, 'columnName' => 'issue_key']);
$metadata->mapField(['fieldName' => 'title', 'type' => 'string', 'length' => 240]);
$metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'length' => 24]);
$metadata->mapField(['fieldName' => 'priority', 'type' => 'string', 'length' => 16]);
$metadata->mapField(['fieldName' => 'dueAt', 'type' => 'datetime_immutable', 'nullable' => true, 'columnName' => 'due_at']);
$metadata->mapField(['fieldName' => 'epicId', 'type' => 'integer', 'nullable' => true, 'columnName' => 'epic_id']);
$metadata->mapField(['fieldName' => 'sprintId', 'type' => 'integer', 'nullable' => true, 'columnName' => 'sprint_id']);
$metadata->mapField(['fieldName' => 'type', 'type' => 'string', 'length' => 24]);
$metadata->mapField(['fieldName' => 'storyPoints', 'type' => 'integer', 'nullable' => true, 'columnName' => 'story_points']);
$metadata->mapField(['fieldName' => 'reporterName', 'type' => 'string', 'length' => 180, 'columnName' => 'reporter_name']);
$metadata->mapField(['fieldName' => 'assigneeNames', 'type' => 'json', 'columnName' => 'assignee_names']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
$metadata->mapField(['fieldName' => 'startAt', 'type' => 'date_immutable', 'nullable' => true, 'columnName' => 'start_at']);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text', 'nullable' => true]);
