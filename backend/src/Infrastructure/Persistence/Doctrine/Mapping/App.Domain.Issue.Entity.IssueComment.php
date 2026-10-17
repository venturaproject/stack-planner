<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_issue_comments', 'indexes' => ['idx_planner_comment_issue' => ['columns' => ['issue_id']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'issueId', 'type' => 'integer', 'columnName' => 'issue_id']);
$metadata->mapField(['fieldName' => 'authorName', 'type' => 'string', 'length' => 180, 'columnName' => 'author_name']);
$metadata->mapField(['fieldName' => 'content', 'type' => 'text']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
