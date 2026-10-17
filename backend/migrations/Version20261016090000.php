<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261016090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Planner projects and issues demo contexts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE planner_projects (id SERIAL NOT NULL, name VARCHAR(160) NOT NULL, project_key VARCHAR(8) NOT NULL, description TEXT NOT NULL, status VARCHAR(24) NOT NULL, cover_image VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))");
        $this->addSql('CREATE UNIQUE INDEX uniq_planner_project_key ON planner_projects (project_key)');
        $this->addSql("CREATE TABLE planner_issues (id SERIAL NOT NULL, project_id INT NOT NULL, issue_key VARCHAR(24) NOT NULL, title VARCHAR(240) NOT NULL, status VARCHAR(24) NOT NULL, priority VARCHAR(16) NOT NULL, due_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))");
        $this->addSql('CREATE UNIQUE INDEX uniq_planner_issue_key ON planner_issues (issue_key)');
        $this->addSql('CREATE INDEX idx_planner_issue_project_status ON planner_issues (project_id, status)');
        $this->addSql('ALTER TABLE planner_issues ADD CONSTRAINT fk_planner_issue_project FOREIGN KEY (project_id) REFERENCES planner_projects (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues DROP CONSTRAINT fk_planner_issue_project');
        $this->addSql('DROP TABLE planner_issues');
        $this->addSql('DROP TABLE planner_projects');
    }
}
