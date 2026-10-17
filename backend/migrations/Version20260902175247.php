<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260902175247 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Rename audit_logs' chain_sequence unique index to Doctrine's own naming convention";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER INDEX uniq_audit_logs_chain_sequence RENAME TO UNIQ_D62F2858B8F8E0FA');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER INDEX uniq_d62f2858b8f8e0fa RENAME TO uniq_audit_logs_chain_sequence');
    }
}
