<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Preserve existing issue and project rows while adding Planner detail fields.
 */
final class Version20261016210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add issue type, estimate, reporter and assignee metadata plus project date ranges.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE planner_issues ADD type VARCHAR(24) DEFAULT 'task' NOT NULL");
        $this->addSql('ALTER TABLE planner_issues ADD story_points INT DEFAULT NULL');
        $this->addSql("ALTER TABLE planner_issues ADD reporter_name VARCHAR(180) DEFAULT 'Admin User' NOT NULL");
        $this->addSql("ALTER TABLE planner_issues ADD assignee_names JSON DEFAULT '[]'::json NOT NULL");
        $this->addSql('ALTER TABLE planner_issues ALTER type DROP DEFAULT');
        $this->addSql('ALTER TABLE planner_issues ALTER reporter_name DROP DEFAULT');
        $this->addSql('ALTER TABLE planner_issues ALTER assignee_names DROP DEFAULT');
        $this->addSql('ALTER TABLE planner_projects ADD start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE planner_projects ADD end_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues DROP type');
        $this->addSql('ALTER TABLE planner_issues DROP story_points');
        $this->addSql('ALTER TABLE planner_issues DROP reporter_name');
        $this->addSql('ALTER TABLE planner_issues DROP assignee_names');
        $this->addSql('ALTER TABLE planner_projects DROP start_date');
        $this->addSql('ALTER TABLE planner_projects DROP end_date');
    }
}
