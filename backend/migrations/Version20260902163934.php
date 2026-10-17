<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902163934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add sent_emails, deduplicating outbox-triggered email sends by outbox event id';
    }

    public function up(Schema $schema): void
    {
        // Not a Doctrine-mapped entity, same as outbox_events itself — written/read
        // through raw DBAL by EmailIdempotencyGuard, purely an infrastructure
        // bookkeeping table with no domain meaning of its own.
        $this->addSql('CREATE TABLE sent_emails (id UUID NOT NULL, outbox_event_id VARCHAR(36) NOT NULL, sent_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SENT_EMAILS_OUTBOX_EVENT_ID ON sent_emails (outbox_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sent_emails');
    }
}
