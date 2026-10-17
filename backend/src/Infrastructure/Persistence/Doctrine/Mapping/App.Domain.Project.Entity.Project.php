<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable([
    'name' => 'planner_projects',
    'uniqueConstraints' => ['uniq_planner_project_key' => ['columns' => ['project_key']]],
]);
$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);
$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 160]);
$metadata->mapField(['fieldName' => 'projectKey', 'type' => 'string', 'length' => 8, 'columnName' => 'project_key']);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text']);
$metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'length' => 24]);
$metadata->mapField(['fieldName' => 'coverImage', 'type' => 'string', 'length' => 255, 'columnName' => 'cover_image']);
$metadata->mapField(['fieldName' => 'color', 'type' => 'string', 'length' => 7]);
$metadata->mapField(['fieldName' => 'startDate', 'type' => 'date_immutable', 'nullable' => true, 'columnName' => 'start_date']);
$metadata->mapField(['fieldName' => 'endDate', 'type' => 'date_immutable', 'nullable' => true, 'columnName' => 'end_date']);
$metadata->mapField(['fieldName' => 'createdAt', 'type' => 'datetime_immutable', 'columnName' => 'created_at']);
