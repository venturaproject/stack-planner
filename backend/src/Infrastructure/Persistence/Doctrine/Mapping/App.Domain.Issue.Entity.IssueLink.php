<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_issue_links', 'uniqueConstraints' => ['uniq_planner_issue_link' => ['columns' => ['source_issue_id', 'target_issue_id', 'link_type']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'sourceIssueId', 'type' => 'integer', 'columnName' => 'source_issue_id']);
$metadata->mapField(['fieldName' => 'targetIssueId', 'type' => 'integer', 'columnName' => 'target_issue_id']);
$metadata->mapField(['fieldName' => 'linkType', 'type' => 'string', 'length' => 32, 'columnName' => 'link_type']);
