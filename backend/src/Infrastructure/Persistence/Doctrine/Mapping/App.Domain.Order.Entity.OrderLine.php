<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
$metadata->setPrimaryTable(['name' => 'order_lines']);

$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField([
    'fieldName' => 'productId',
    'type' => 'integer',
    'columnName' => 'product_id',
]);
$metadata->mapField([
    'fieldName' => 'productSku',
    'type' => 'string',
    'length' => 32,
    'columnName' => 'product_sku',
]);
$metadata->mapField([
    'fieldName' => 'productName',
    'type' => 'string',
    'length' => 150,
    'columnName' => 'product_name',
]);
$metadata->mapField([
    'fieldName' => 'unitPriceAmountMinorUnits',
    'type' => 'integer',
    'columnName' => 'unit_price_amount_minor_units',
]);
$metadata->mapField([
    'fieldName' => 'unitPriceCurrency',
    'type' => 'string',
    'length' => 3,
    'columnName' => 'unit_price_currency',
]);
$metadata->mapField(['fieldName' => 'quantity', 'type' => 'integer']);

$metadata->mapManyToOne([
    'fieldName' => 'order',
    'targetEntity' => App\Domain\Order\Entity\Order::class,
    'inversedBy' => 'lines',
    'joinColumns' => [[
        'name' => 'order_id',
        'referencedColumnName' => 'id',
        'nullable' => false,
        'onDelete' => 'CASCADE',
    ]],
]);
