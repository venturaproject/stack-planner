<?php

declare(strict_types=1);

namespace App\Application\Order\Command;

use App\Application\Order\DTO\OrderResponse;
use App\Domain\Customer\Exception\CustomerNotFoundException;
use App\Domain\Customer\Repository\CustomerRepositoryInterface;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\Exception\OrderNotFoundException;
use App\Domain\Order\Repository\OrderRepositoryInterface;
use App\Domain\Product\Exception\ProductNotFoundException;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Either appends a line to an existing Draft order (orderId given) or starts a new
 * Draft order for the given customer and adds the first line to it — mirrors a
 * typical "add to cart" flow where the cart is created implicitly on first add.
 */
#[AsMessageHandler(bus: 'command.bus', method: 'handle')]
final class AddOrderLineHandler
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly CustomerRepositoryInterface $customers,
        private readonly ProductRepositoryInterface $products,
    ) {
    }

    public function handle(AddOrderLineCommand $cmd): OrderResponse
    {
        if (null !== $cmd->orderId) {
            $order = $this->orders->findById($cmd->orderId);
            if (null === $order) {
                throw new OrderNotFoundException();
            }
        } else {
            if (null === $cmd->customerId) {
                throw new InvalidArgumentException('customerId is required to start a new order');
            }
            $customer = $this->customers->findById($cmd->customerId);
            if (null === $customer) {
                throw new CustomerNotFoundException();
            }
            $order = Order::startDraft($customer->requireId(), $customer->getAddress());
        }

        $product = $this->products->findById($cmd->productId);
        if (null === $product) {
            throw new ProductNotFoundException();
        }

        $order->addLine($product->requireId(), $product->getSku(), $product->getName(), $product->getPrice(), $cmd->quantity);

        $this->orders->save($order);

        return OrderResponse::from($order);
    }
}
