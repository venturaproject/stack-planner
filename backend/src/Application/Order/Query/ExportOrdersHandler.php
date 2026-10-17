<?php

declare(strict_types=1);

namespace App\Application\Order\Query;

use App\Application\Shared\DTO\TabularExport;
use App\Domain\Order\ValueObject\OrderStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus', method: 'handle')]
final class ExportOrdersHandler
{
    private const MAX_ROWS = 10000;

    public function __construct(private readonly OrderReadModelInterface $orders)
    {
    }

    public function handle(ExportOrdersQuery $query): TabularExport
    {
        $status = null !== $query->status ? OrderStatus::from($query->status) : null;
        $items = $query->all
            ? $this->orders->findForExport($query->customerId, $status, self::MAX_ROWS + 1, $query->createdFrom, $query->createdTo)
            : $this->orders->findByIds($query->ids);
        $limitExceeded = \count($items) > self::MAX_ROWS;
        $items = \array_slice($items, 0, self::MAX_ROWS);

        return new TabularExport(array_map(static function ($order) {
            $total = $order->getTotal();

            return [$order->requireId(), $order->getCustomerId(), $order->getStatus()->value,
                null !== $total ? number_format($total->amountMinorUnits() / 100, 2) : '', $total?->currency()->value ?? '', \count($order->getLines())];
        }, $items), $limitExceeded);
    }
}
