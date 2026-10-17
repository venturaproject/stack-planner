<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_issue_worklogs', 'indexes' => ['idx_planner_worklog_issue' => ['columns' => ['issue_id']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'issueId', 'type' => 'integer', 'columnName' => 'issue_id']);
$metadata->mapField(['fieldName' => 'authorName', 'type' => 'string', 'length' => 180, 'columnName' => 'author_name']);
$metadata->mapField(['fieldName' => 'minutes', 'type' => 'integer']);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text']);
$metadata->mapField(['fieldName' => 'loggedAt', 'type' => 'datetime_immutable', 'columnName' => 'logged_at']);
