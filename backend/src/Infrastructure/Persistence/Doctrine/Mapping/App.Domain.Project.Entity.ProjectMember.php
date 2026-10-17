<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'planner_project_members', 'uniqueConstraints' => ['uniq_planner_project_member' => ['columns' => ['project_id', 'user_id']]]]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'projectId', 'type' => 'integer', 'columnName' => 'project_id']);
$metadata->mapField(['fieldName' => 'userId', 'type' => 'string', 'length' => 36, 'columnName' => 'user_id']);
$metadata->mapField(['fieldName' => 'role', 'type' => 'string', 'length' => 24]);
