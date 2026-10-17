<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable([
    'name' => 'products',
    'uniqueConstraints' => [
        'uniq_products_sku' => ['columns' => ['sku']],
    ],
]);

$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField([
    'fieldName' => 'sku',
    'type' => 'string',
    'length' => 32,
    'columnName' => 'sku',
]);

$metadata->mapField(['fieldName' => 'name', 'type' => 'string', 'length' => 150]);
$metadata->mapField(['fieldName' => 'description', 'type' => 'text', 'nullable' => true]);

$metadata->mapField([
    'fieldName' => 'priceAmountMinorUnits',
    'type' => 'integer',
    'columnName' => 'price_amount_minor_units',
]);
$metadata->mapField([
    'fieldName' => 'priceCurrency',
    'type' => 'string',
    'length' => 3,
    'columnName' => 'price_currency',
]);

$metadata->mapField([
    'fieldName' => 'stockQuantity',
    'type' => 'integer',
    'columnName' => 'stock_quantity',
]);

$metadata->mapField([
    'fieldName' => 'imageFilename',
    'type' => 'string',
    'length' => 255,
    'nullable' => true,
    'columnName' => 'image_filename',
]);

$metadata->mapField([
    'fieldName' => 'createdAt',
    'type' => 'datetime_immutable',
    'columnName' => 'created_at',
]);
