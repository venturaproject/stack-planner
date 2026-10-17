<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Store project theme colors and associate demo issues with their epics/sprints.
 */
final class Version20261016150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add project color and optional epic and sprint identifiers to issues.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues ADD epic_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE planner_issues ADD sprint_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE planner_projects ADD color VARCHAR(7) DEFAULT NULL');
        $this->addSql("UPDATE planner_projects SET color = '#6366f1' WHERE color IS NULL");
        $this->addSql('ALTER TABLE planner_projects ALTER color SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues DROP epic_id');
        $this->addSql('ALTER TABLE planner_issues DROP sprint_id');
        $this->addSql('ALTER TABLE planner_projects DROP color');
    }
}
