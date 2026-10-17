<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_issue_attachments', 'indexes' => ['idx_planner_attachment_issue' => ['columns' => ['issue_id']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'issueId', 'type' => 'integer', 'columnName' => 'issue_id']);
$metadata->mapField(['fieldName' => 'filename', 'type' => 'string', 'length' => 255]);
$metadata->mapField(['fieldName' => 'originalName', 'type' => 'string', 'length' => 255, 'columnName' => 'original_name']);
$metadata->mapField(['fieldName' => 'mimeType', 'type' => 'string', 'length' => 120, 'columnName' => 'mime_type']);
$metadata->mapField(['fieldName' => 'sizeBytes', 'type' => 'integer', 'columnName' => 'size_bytes']);
