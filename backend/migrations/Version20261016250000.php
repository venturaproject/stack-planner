<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add issue descriptions to support the source create/edit form.
 */
final class Version20261016250000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add optional rich text description field for issues.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues ADD description TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE planner_issues DROP description');
    }
}
