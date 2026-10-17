<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add issue creation timestamps for the dashboard activity charts.
 */
final class Version20261016240000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track issue creation timestamps for project and dashboard reporting.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE planner_issues ALTER created_at DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues DROP created_at');
    }
}
