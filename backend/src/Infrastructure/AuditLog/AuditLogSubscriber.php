<?php

declare(strict_types=1);

namespace App\Infrastructure\AuditLog;

use App\Domain\Auth\Event\RoleCreated;
use App\Domain\Auth\Event\RolePermissionsChanged;
use App\Domain\Auth\Event\UserCreated;
use App\Domain\Auth\Event\UserDeactivated;
use App\Domain\Auth\Event\UserImpersonated;
use App\Domain\Auth\Event\UserLoggedIn;
use App\Domain\Auth\Event\UserPasswordChanged;
use App\Domain\Auth\Event\UserTotpDisabled;
use App\Domain\Auth\Event\UserTotpEnabled;
use App\Domain\Auth\Event\UserUpdated;
use App\Domain\Customer\Event\CustomerAddressChanged;
use App\Domain\Customer\Event\CustomerRegistered;
use App\Domain\Order\Event\OrderCancelled;
use App\Domain\Order\Event\OrderPlaced;
use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductPriceChanged;
use App\Domain\Product\Event\StockAdjusted;
use App\Domain\Settings\Event\SecuritySettingsUpdated;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AuditLogSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AuditLogRecorder $recorder)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UserLoggedIn::class => 'onUserLoggedIn',
            UserCreated::class => 'onUserCreated',
            UserUpdated::class => 'onUserUpdated',
            UserPasswordChanged::class => 'onUserPasswordChanged',
            UserImpersonated::class => 'onUserImpersonated',
            UserDeactivated::class => 'onUserDeactivated',
            ProductCreated::class => 'onProductCreated',
            ProductPriceChanged::class => 'onProductPriceChanged',
            StockAdjusted::class => 'onStockAdjusted',
            CustomerRegistered::class => 'onCustomerRegistered',
            CustomerAddressChanged::class => 'onCustomerAddressChanged',
            OrderPlaced::class => 'onOrderPlaced',
            OrderCancelled::class => 'onOrderCancelled',
            UserTotpEnabled::class => 'onUserTotpEnabled',
            UserTotpDisabled::class => 'onUserTotpDisabled',
            SecuritySettingsUpdated::class => 'onSecuritySettingsUpdated',
            RoleCreated::class => 'onRoleCreated',
            RolePermissionsChanged::class => 'onRolePermissionsChanged',
        ];
    }

    public function onUserLoggedIn(UserLoggedIn $event): void
    {
        $this->recorder->record(
            action: 'user.login',
            resourceType: 'User',
            resourceId: $event->userId,
            explicitActorId: $event->userId,
            explicitActorEmail: $event->email,
        );
    }

    public function onUserCreated(UserCreated $event): void
    {
        $this->recorder->record(
            action: 'user.created',
            resourceType: 'User',
            resourceId: $event->userId,
            metadata: ['email' => $event->email],
        );
    }

    public function onUserUpdated(UserUpdated $event): void
    {
        $this->recorder->record(
            action: 'user.updated',
            resourceType: 'User',
            resourceId: $event->userId,
            metadata: ['email' => $event->email],
        );
    }

    public function onUserPasswordChanged(UserPasswordChanged $event): void
    {
        $this->recorder->record(
            action: 'user.password_changed',
            resourceType: 'User',
            resourceId: $event->userId,
        );
    }

    public function onUserImpersonated(UserImpersonated $event): void
    {
        // No explicit actor: the security token at this point in the request is still
        // the admin's (the impersonation JWT isn't in use yet), so the recorder's
        // fallback to the current token's user already attributes this correctly.
        $this->recorder->record(
            action: 'user.impersonated',
            resourceType: 'User',
            resourceId: $event->userId,
            metadata: ['impersonatorEmail' => $event->impersonatorEmail],
        );
    }

    public function onUserTotpEnabled(UserTotpEnabled $event): void
    {
        $this->recorder->record(
            action: 'user.totp_enabled',
            resourceType: 'User',
            resourceId: $event->userId,
        );
    }

    public function onUserTotpDisabled(UserTotpDisabled $event): void
    {
        $this->recorder->record(
            action: 'user.totp_disabled',
            resourceType: 'User',
            resourceId: $event->userId,
        );
    }

    public function onSecuritySettingsUpdated(SecuritySettingsUpdated $event): void
    {
        $this->recorder->record(
            action: 'security_settings.updated',
            resourceType: 'SecuritySettings',
            resourceId: null,
            metadata: ['require2fa' => $event->require2fa],
        );
    }

    public function onUserDeactivated(UserDeactivated $event): void
    {
        $this->recorder->record(
            action: 'user.deactivated',
            resourceType: 'User',
            resourceId: $event->userId,
            metadata: ['email' => $event->email],
        );
    }

    public function onProductCreated(ProductCreated $event): void
    {
        $this->recorder->record(
            action: 'product.created',
            resourceType: 'Product',
            resourceId: null !== $event->productId ? (string) $event->productId : null,
            metadata: ['sku' => $event->sku, 'name' => $event->name],
        );
    }

    public function onProductPriceChanged(ProductPriceChanged $event): void
    {
        $this->recorder->record(
            action: 'product.price_changed',
            resourceType: 'Product',
            resourceId: null !== $event->productId ? (string) $event->productId : null,
            metadata: [
                'previousAmountMinorUnits' => $event->previousAmountMinorUnits,
                'newAmountMinorUnits' => $event->newAmountMinorUnits,
                'currency' => $event->currency,
            ],
        );
    }

    public function onStockAdjusted(StockAdjusted $event): void
    {
        $this->recorder->record(
            action: 'product.stock_adjusted',
            resourceType: 'Product',
            resourceId: null !== $event->productId ? (string) $event->productId : null,
            metadata: ['delta' => $event->delta, 'newQuantity' => $event->newQuantity, 'reason' => $event->reason],
        );
    }

    public function onCustomerRegistered(CustomerRegistered $event): void
    {
        $this->recorder->record(
            action: 'customer.registered',
            resourceType: 'Customer',
            resourceId: null !== $event->customerId ? (string) $event->customerId : null,
            metadata: ['email' => $event->email, 'name' => $event->name],
        );
    }

    public function onCustomerAddressChanged(CustomerAddressChanged $event): void
    {
        $this->recorder->record(
            action: 'customer.address_changed',
            resourceType: 'Customer',
            resourceId: null !== $event->customerId ? (string) $event->customerId : null,
        );
    }

    public function onOrderPlaced(OrderPlaced $event): void
    {
        $this->recorder->record(
            action: 'order.placed',
            resourceType: 'Order',
            resourceId: null !== $event->orderId ? (string) $event->orderId : null,
            metadata: [
                'customerId' => $event->customerId,
                'totalAmountMinorUnits' => $event->totalAmountMinorUnits,
                'currency' => $event->currency,
            ],
        );
    }

    public function onOrderCancelled(OrderCancelled $event): void
    {
        $this->recorder->record(
            action: 'order.cancelled',
            resourceType: 'Order',
            resourceId: null !== $event->orderId ? (string) $event->orderId : null,
        );
    }

    public function onRoleCreated(RoleCreated $event): void
    {
        $this->recorder->record(
            action: 'role.created',
            resourceType: 'Role',
            resourceId: $event->roleId,
            metadata: ['name' => $event->name],
        );
    }

    public function onRolePermissionsChanged(RolePermissionsChanged $event): void
    {
        $this->recorder->record(
            action: 'role.permissions_changed',
            resourceType: 'Role',
            resourceId: $event->roleId,
            metadata: ['name' => $event->name, 'permissions' => $event->permissionNames],
        );
    }
}
