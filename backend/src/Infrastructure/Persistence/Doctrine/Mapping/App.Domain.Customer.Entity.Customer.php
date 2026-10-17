<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable([
    'name' => 'customers',
    'uniqueConstraints' => [
        'uniq_customers_email' => ['columns' => ['email']],
    ],
]);

$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 150]);
$metadata->mapField(['fieldName' => 'email', 'type' => 'string', 'length' => 180]);

$metadata->mapField([
    'fieldName' => 'addressStreet',
    'type' => 'string',
    'length' => 200,
    'nullable' => true,
    'columnName' => 'address_street',
]);
$metadata->mapField([
    'fieldName' => 'addressCity',
    'type' => 'string',
    'length' => 100,
    'nullable' => true,
    'columnName' => 'address_city',
]);
$metadata->mapField([
    'fieldName' => 'addressPostalCode',
    'type' => 'string',
    'length' => 20,
    'nullable' => true,
    'columnName' => 'address_postal_code',
]);
$metadata->mapField([
    'fieldName' => 'addressCountry',
    'type' => 'string',
    'length' => 100,
    'nullable' => true,
    'columnName' => 'address_country',
]);

$metadata->mapField([
    'fieldName' => 'createdAt',
    'type' => 'datetime_immutable',
    'columnName' => 'created_at',
]);
