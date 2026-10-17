<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add outbox_events.failed_at so events exhausting their retries stop being picked up instead of retrying forever';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE outbox_events ADD failed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_OUTBOX_FAILED ON outbox_events (failed_at) WHERE failed_at IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_OUTBOX_FAILED');
        $this->addSql('ALTER TABLE outbox_events DROP failed_at');
    }
}
