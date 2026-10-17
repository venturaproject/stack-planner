<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track one-time user account activation links';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD activation_version INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP activation_version');
    }
}
