<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260821110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for order filtering and notification delivery';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_orders_customer_created_at ON orders (customer_id, created_at DESC)');
        $this->addSql('CREATE INDEX idx_orders_status_created_at ON orders (status, created_at DESC)');
        $this->addSql('CREATE INDEX idx_notifications_user_created_at ON notifications (user_id, created_at DESC)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_orders_customer_created_at');
        $this->addSql('DROP INDEX idx_orders_status_created_at');
        $this->addSql('DROP INDEX idx_notifications_user_created_at');
    }
}
