<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818120049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add TOTP fields to users and a single-row security_settings table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE security_settings (id VARCHAR(32) NOT NULL, require_2fa BOOLEAN NOT NULL, PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE users ADD totp_secret VARCHAR(255) DEFAULT NULL');
        // DEFAULT is required here: this table already has rows (the seeded admin,
        // any real data) and these two columns are NOT NULL.
        $this->addSql("ALTER TABLE users ADD totp_enabled BOOLEAN NOT NULL DEFAULT FALSE");
        $this->addSql("ALTER TABLE users ADD totp_backup_code_hashes JSON NOT NULL DEFAULT '[]'");
        $this->addSql('ALTER TABLE users ALTER COLUMN totp_enabled DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER COLUMN totp_backup_code_hashes DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE security_settings');
        $this->addSql('ALTER TABLE users DROP totp_secret');
        $this->addSql('ALTER TABLE users DROP totp_enabled');
        $this->addSql('ALTER TABLE users DROP totp_backup_code_hashes');
    }
}
