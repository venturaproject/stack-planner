<?php

declare(strict_types=1);

namespace App\Application\Customer\Query;

use App\Application\Customer\DTO\CustomerResponse;
use App\Application\Shared\DTO\PagedResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ListCustomersHandler
{
    public function __construct(private readonly CustomerReadModelInterface $customers)
    {
    }

    /** @return PagedResponse<CustomerResponse> */
    public function handle(ListCustomersQuery $query): PagedResponse
    {
        $items = $this->customers->findPaginated($query->page, $query->size, $query->search, $query->hasAddress, $query->createdFrom, $query->createdTo);
        $total = $this->customers->countAll($query->search, $query->hasAddress, $query->createdFrom, $query->createdTo);

        $totalAll = $this->customers->countAll(null);
        $withAddress = $this->customers->countWithAddress();
        $stats = [
            'total' => $totalAll,
            'withAddress' => $withAddress,
            'withoutAddress' => $totalAll - $withAddress,
        ];

        return PagedResponse::of($items, $query->page, $query->size, $total, CustomerResponse::from(...), $stats);
    }
}
