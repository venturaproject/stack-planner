<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop the redundant DB-level default on users.activation_version — the domain entity always sets it explicitly';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ALTER activation_version DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ALTER activation_version SET DEFAULT 0');
    }
}
