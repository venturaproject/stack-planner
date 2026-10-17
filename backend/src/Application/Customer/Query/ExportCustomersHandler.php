<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

use App\Application\Shared\DTO\TabularExport;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ExportCustomersHandler
{
    private const MAX_ROWS = 10000;

    public function __construct(private readonly CustomerReadModelInterface $customers)
    {
    }

    public function handle(ExportCustomersQuery $query): TabularExport
    {
        $items = $query->all
            ? $this->customers->findForExport($query->search, $query->hasAddress, self::MAX_ROWS + 1, $query->createdFrom, $query->createdTo)
            : $this->customers->findByIds($query->ids);
        $limitExceeded = \count($items) > self::MAX_ROWS;
        $items = \array_slice($items, 0, self::MAX_ROWS);

        return new TabularExport(array_map(static function ($customer) {
            $address = $customer->getAddress();

            $addressColumns = null === $address
                ? ['', '', '', '']
                : [$address->street, $address->city, $address->postalCode, $address->country];

            return [$customer->requireId(), $customer->getName(), $customer->getEmail()->value(), ...$addressColumns];
        }, $items), $limitExceeded);
    }
}
