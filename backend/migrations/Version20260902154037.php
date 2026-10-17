<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902154037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add notifications.outbox_event_id so redelivered outbox events cannot create a duplicate notification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notifications ADD outbox_event_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6000B0D38E01A2E3 ON notifications (outbox_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_6000B0D38E01A2E3');
        $this->addSql('ALTER TABLE notifications DROP outbox_event_id');
    }
}
