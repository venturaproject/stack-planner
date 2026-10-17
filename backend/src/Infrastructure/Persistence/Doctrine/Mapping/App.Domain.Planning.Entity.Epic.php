<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_epics', 'uniqueConstraints' => ['uniq_planner_epic_key' => ['columns' => ['epic_key']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'projectId', 'type' => 'integer', 'columnName' => 'project_id']);
$metadata->mapField(['fieldName' => 'epicKey', 'type' => 'string', 'length' => 24, 'columnName' => 'epic_key']);
$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 180]);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text']);
$metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'length' => 24]);
$metadata->mapField(['fieldName' => 'progress', 'type' => 'integer']);
