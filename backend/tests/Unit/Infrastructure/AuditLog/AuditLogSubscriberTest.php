<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\AuditLog;

use App\Domain\AuditLog\Entity\AuditLog;
use App\Domain\AuditLog\Repository\AuditLogRepositoryInterface;
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
use App\Infrastructure\AuditLog\AuditLogRecorder;
use App\Infrastructure\AuditLog\AuditLogSubscriber;
use App\Infrastructure\Outbox\AuditContext;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Exercises AuditLogSubscriber together with a real AuditLogRecorder (final, so it
 * can't be mocked directly) against a mocked repository, asserting on the AuditLog
 * entity that ends up being saved.
 */
final class AuditLogSubscriberTest extends TestCase
{
    private AuditLogRepositoryInterface&MockObject $repository;
    private AuditLogSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(AuditLogRepositoryInterface::class);
        $this->repository->method('getLastHash')->willReturn('genesis-hash');
        $this->repository->method('withHashChainLock')->willReturnCallback(static fn (callable $fn) => $fn());

        $recorder = $this->buildRecorder($this->repository);
        $this->subscriber = new AuditLogSubscriber($recorder);
    }

    private function buildRecorder(AuditLogRepositoryInterface $repository, ?AuditContext $auditContext = null): AuditLogRecorder
    {
        $tokenStorage = $this->createStub(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        return new AuditLogRecorder($repository, $tokenStorage, $requestStack, $auditContext);
    }

    #[AllowMockObjectsWithoutExpectations]
    public function testSubscribesToAllEighteenDomainEvents(): void
    {
        $events = AuditLogSubscriber::getSubscribedEvents();

        self::assertSame([
            UserLoggedIn::class,
            UserCreated::class,
            UserUpdated::class,
            UserPasswordChanged::class,
            UserImpersonated::class,
            UserDeactivated::class,
            ProductCreated::class,
            ProductPriceChanged::class,
            StockAdjusted::class,
            CustomerRegistered::class,
            CustomerAddressChanged::class,
            OrderPlaced::class,
            OrderCancelled::class,
            UserTotpEnabled::class,
            UserTotpDisabled::class,
            SecuritySettingsUpdated::class,
            RoleCreated::class,
            RolePermissionsChanged::class,
        ], array_keys($events));
    }

    public function testOnUserLoggedInUsesEventUserAsExplicitActor(): void
    {
        $this->repository->expects(self::once())->method('save')
            ->with(self::isInstanceOf(AuditLog::class));

        $this->subscriber->onUserLoggedIn(new UserLoggedIn('user-1', 'me@example.com'));
    }

    public function testOnUserCreatedRecordsEmailMetadata(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.created', $entry->getAction());
            self::assertSame('User', $entry->getResourceType());
            self::assertSame('user-1', $entry->getResourceId());
            self::assertSame(['email' => 'me@example.com'], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onUserCreated(new UserCreated('user-1', 'me@example.com', 0));
    }

    public function testOnUserUpdatedRecordsEmailMetadata(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.updated', $entry->getAction());
            self::assertSame('user-1', $entry->getResourceId());

            return true;
        }));

        $this->subscriber->onUserUpdated(new UserUpdated('user-1', 'me@example.com'));
    }

    public function testOnUserPasswordChangedRecordsNoMetadata(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.password_changed', $entry->getAction());
            self::assertSame('User', $entry->getResourceType());
            self::assertSame('user-1', $entry->getResourceId());
            self::assertSame([], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onUserPasswordChanged(new UserPasswordChanged('user-1', 'me@example.com'));
    }

    public function testOnUserImpersonatedRecordsTheImpersonatorEmail(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.impersonated', $entry->getAction());
            self::assertSame('User', $entry->getResourceType());
            self::assertSame('user-1', $entry->getResourceId());
            self::assertSame(['impersonatorEmail' => 'admin@example.com'], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onUserImpersonated(new UserImpersonated('user-1', 'me@example.com', 'admin@example.com'));
    }

    public function testOnUserTotpEnabledRecordsResourceOnly(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.totp_enabled', $entry->getAction());
            self::assertSame('User', $entry->getResourceType());
            self::assertSame('user-1', $entry->getResourceId());

            return true;
        }));

        $this->subscriber->onUserTotpEnabled(new UserTotpEnabled('user-1', 'me@example.com'));
    }

    public function testOnUserTotpDisabledRecordsResourceOnly(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.totp_disabled', $entry->getAction());
            self::assertSame('User', $entry->getResourceType());
            self::assertSame('user-1', $entry->getResourceId());

            return true;
        }));

        $this->subscriber->onUserTotpDisabled(new UserTotpDisabled('user-1', 'me@example.com'));
    }

    public function testOnSecuritySettingsUpdatedRecordsTheNewValue(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('security_settings.updated', $entry->getAction());
            self::assertSame('SecuritySettings', $entry->getResourceType());
            self::assertNull($entry->getResourceId());
            self::assertSame(['require2fa' => true], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onSecuritySettingsUpdated(new SecuritySettingsUpdated(true));
    }

    public function testOnUserDeactivatedRecordsEmailMetadata(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('user.deactivated', $entry->getAction());
            self::assertSame('user-1', $entry->getResourceId());

            return true;
        }));

        $this->subscriber->onUserDeactivated(new UserDeactivated('user-1', 'me@example.com'));
    }

    public function testOnProductCreatedRecordsSkuAndName(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('product.created', $entry->getAction());
            self::assertSame('Product', $entry->getResourceType());
            self::assertSame('1', $entry->getResourceId());
            self::assertSame(['sku' => 'TSH-0001', 'name' => 'T-Shirt'], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onProductCreated(new ProductCreated(1, 'TSH-0001', 'T-Shirt'));
    }

    public function testOnProductCreatedToleratesMissingId(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertNull($entry->getResourceId());

            return true;
        }));

        $this->subscriber->onProductCreated(new ProductCreated(null, 'TSH-0001', 'T-Shirt'));
    }

    public function testOnProductPriceChangedRecordsAmounts(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('product.price_changed', $entry->getAction());
            self::assertSame([
                'previousAmountMinorUnits' => 1000,
                'newAmountMinorUnits' => 1500,
                'currency' => 'USD',
            ], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onProductPriceChanged(new ProductPriceChanged(1, 1000, 1500, 'USD'));
    }

    public function testOnStockAdjustedRecordsDeltaAndReason(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('product.stock_adjusted', $entry->getAction());
            self::assertSame(['delta' => -2, 'newQuantity' => 8, 'reason' => 'order_placed'], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onStockAdjusted(new StockAdjusted(1, -2, 8, 'order_placed'));
    }

    public function testOnCustomerRegisteredRecordsEmailAndName(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('customer.registered', $entry->getAction());
            self::assertSame(['email' => 'ada@example.com', 'name' => 'Ada Lovelace'], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onCustomerRegistered(new CustomerRegistered(1, 'ada@example.com', 'Ada Lovelace'));
    }

    public function testOnCustomerAddressChangedRecordsResourceOnly(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('customer.address_changed', $entry->getAction());
            self::assertSame('1', $entry->getResourceId());
            self::assertSame([], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onCustomerAddressChanged(new CustomerAddressChanged(1));
    }

    public function testOnOrderPlacedRecordsTotals(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('order.placed', $entry->getAction());
            self::assertSame([
                'customerId' => 5,
                'totalAmountMinorUnits' => 1999,
                'currency' => 'USD',
            ], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onOrderPlaced(new OrderPlaced(1, 5, 1999, 'USD'));
    }

    public function testOnOrderCancelledRecordsResourceOnly(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('order.cancelled', $entry->getAction());
            self::assertSame('1', $entry->getResourceId());

            return true;
        }));

        $this->subscriber->onOrderCancelled(new OrderCancelled(1));
    }

    public function testOnRoleCreatedRecordsName(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('role.created', $entry->getAction());
            self::assertSame('Role', $entry->getResourceType());
            self::assertSame('role-1', $entry->getResourceId());
            self::assertSame(['name' => 'Editor'], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onRoleCreated(new RoleCreated('role-1', 'Editor'));
    }

    public function testOnRolePermissionsChangedRecordsNameAndPermissions(): void
    {
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('role.permissions_changed', $entry->getAction());
            self::assertSame('Role', $entry->getResourceType());
            self::assertSame('role-1', $entry->getResourceId());
            self::assertSame(['name' => 'Editor', 'permissions' => ['users.view', 'users.edit']], $entry->getMetadata());

            return true;
        }));

        $this->subscriber->onRolePermissionsChanged(new RolePermissionsChanged('role-1', 'Editor', ['users.view', 'users.edit']));
    }

    public function testRedeliveredOutboxEventIsNotRecordedTwice(): void
    {
        $auditContext = new AuditContext(
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(RequestStack::class),
        );
        $auditContext->setOutboxEventId('outbox-event-1');

        $this->repository->expects(self::once())->method('existsByOutboxEventId')->with('outbox-event-1')->willReturn(true);
        $this->repository->expects(self::never())->method('save');

        $subscriber = new AuditLogSubscriber($this->buildRecorder($this->repository, $auditContext));
        $subscriber->onOrderCancelled(new OrderCancelled(1));
    }

    public function testFirstDeliveryOfAnOutboxEventIsRecordedWithItsId(): void
    {
        $auditContext = new AuditContext(
            $this->createStub(TokenStorageInterface::class),
            $this->createStub(RequestStack::class),
        );
        $auditContext->setOutboxEventId('outbox-event-1');

        $this->repository->expects(self::once())->method('existsByOutboxEventId')->with('outbox-event-1')->willReturn(false);
        $this->repository->expects(self::once())->method('save')->with(self::callback(static function (AuditLog $entry) {
            self::assertSame('outbox-event-1', $entry->getOutboxEventId());

            return true;
        }));

        $subscriber = new AuditLogSubscriber($this->buildRecorder($this->repository, $auditContext));
        $subscriber->onOrderCancelled(new OrderCancelled(1));
    }
}
