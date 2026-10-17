<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add transactional domain-event outbox with retry leases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE outbox_events (id UUID NOT NULL, event_type VARCHAR(255) NOT NULL, payload JSONB NOT NULL, context JSONB NOT NULL, occurred_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, attempts INT NOT NULL DEFAULT 0, locked_until TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, processed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, last_error TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_OUTBOX_READY ON outbox_events (available_at, occurred_at) WHERE processed_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE outbox_events');
    }
}
