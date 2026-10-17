<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901173754 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add audit_logs.outbox_event_id so redelivered outbox events cannot be recorded twice';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE audit_logs ADD outbox_event_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F28588E01A2E3 ON audit_logs (outbox_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_D62F28588E01A2E3');
        $this->addSql('ALTER TABLE audit_logs DROP outbox_event_id');
    }
}
