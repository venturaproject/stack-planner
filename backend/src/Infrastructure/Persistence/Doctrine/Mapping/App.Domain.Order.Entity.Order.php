<?php

declare(strict_types=1);

use Doctrine\ORM\Mapping\ClassMetadata;

/* @var ClassMetadata $metadata */
// Both indexes are declared DESC on created_at in the migration (Version20260821110000)
// for the paginated "most recent first" listing queries; Doctrine's Index model doesn't
// track column sort direction, so schema-diff compares only the column lists below and
// won't try to touch the DESC ordering in the database.
$metadata->setPrimaryTable([
    'name' => 'orders',
    'indexes' => [
        'idx_orders_customer_created_at' => ['columns' => ['customer_id', 'created_at']],
        'idx_orders_status_created_at' => ['columns' => ['status', 'created_at']],
    ],
]);

$metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
$metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

$metadata->mapField([
    'fieldName' => 'customerId',
    'type' => 'integer',
    'columnName' => 'customer_id',
]);

$metadata->mapField([
    'fieldName' => 'status',
    'type' => 'string',
    'length' => 20,
    'columnName' => 'status',
    'enumType' => App\Domain\Order\ValueObject\OrderStatus::class,
]);

$metadata->mapField([
    'fieldName' => 'shippingAddressStreet',
    'type' => 'string',
    'length' => 200,
    'nullable' => true,
    'columnName' => 'shipping_address_street',
]);
$metadata->mapField([
    'fieldName' => 'shippingAddressCity',
    'type' => 'string',
    'length' => 100,
    'nullable' => true,
    'columnName' => 'shipping_address_city',
]);
$metadata->mapField([
    'fieldName' => 'shippingAddressPostalCode',
    'type' => 'string',
    'length' => 20,
    'nullable' => true,
    'columnName' => 'shipping_address_postal_code',
]);
$metadata->mapField([
    'fieldName' => 'shippingAddressCountry',
    'type' => 'string',
    'length' => 100,
    'nullable' => true,
    'columnName' => 'shipping_address_country',
]);

$metadata->mapField([
    'fieldName' => 'totalAmountMinorUnits',
    'type' => 'integer',
    'nullable' => true,
    'columnName' => 'total_amount_minor_units',
]);
$metadata->mapField([
    'fieldName' => 'totalCurrency',
    'type' => 'string',
    'length' => 3,
    'nullable' => true,
    'columnName' => 'total_currency',
]);

$metadata->mapField([
    'fieldName' => 'createdAt',
    'type' => 'datetime_immutable',
    'columnName' => 'created_at',
]);

$metadata->mapOneToMany([
    'fieldName' => 'lines',
    'targetEntity' => App\Domain\Order\Entity\OrderLine::class,
    'mappedBy' => 'order',
    'cascade' => ['persist', 'remove'],
    'orphanRemoval' => true,
]);
