<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_sprints']);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'projectId', 'type' => 'integer', 'columnName' => 'project_id']);
$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 120]);
$metadata->mapField(['fieldName' => 'goal', 'type' => 'text']);
$metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'length' => 24]);
$metadata->mapField(['fieldName' => 'startAt', 'type' => 'date_immutable', 'columnName' => 'start_at']);
$metadata->mapField(['fieldName' => 'endAt', 'type' => 'date_immutable', 'columnName' => 'end_at']);
