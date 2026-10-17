<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add start dates to issues for Gantt scheduling and date range tooltips.
 */
final class Version20261016260000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional planned start date for issue scheduling.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues ADD start_at DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues DROP start_at');
    }
}
