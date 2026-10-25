<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

use App\Application\Order\DTO\OrderResponse;
use App\Application\Shared\Transaction\TransactionManagerInterface;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;

/**
 * Coordinates two aggregates for one command: reserves stock on every Product
 * involved (each Product enforces its own stock invariant independently) before
 * transitioning the Order itself to Placed. Neither aggregate reaches into the
 * other's internals — this handler is where the cross-aggregate consistency lives,
 * not inside Order::place() or Product::reserveStock().
 */
final class PlaceOrderHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ProductRepositoryInterface $products,
        private readonly TransactionManagerInterface $transactions,
    ) {
    }

    public function handle(PlaceOrderCommand $cmd): OrderResponse
    {
        return $this->transactions->run(function () use ($cmd): OrderResponse {
            $order = $this->orders->findByIdForUpdate($cmd->orderId);
            if (null === $order) {
                throw new OrderNotFoundException();
            }

            $productIds = array_map(
                static fn ($line): int => $line->getProductId(),
                $order->getLines(),
            );
            $productIds = array_values(array_unique($productIds));
            sort($productIds, \SORT_NUMERIC);

            $products = [];
            foreach ($productIds as $productId) {
                $product = $this->products->findByIdForUpdate($productId);
                if (null === $product) {
                    throw new ProductNotFoundException();
                }

                $products[$productId] = $product;
            }

            foreach ($order->getLines() as $line) {
                $products[$line->getProductId()]->reserveStock($line->getQuantity(), 'order_placed');
            }

            $order->place();

            return OrderResponse::from($order);
        });
    }
}
