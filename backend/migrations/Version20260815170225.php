<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260815170225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE audit_logs (id VARCHAR(36) NOT NULL, actor_id VARCHAR(36) DEFAULT NULL, actor_email VARCHAR(255) DEFAULT NULL, action VARCHAR(100) NOT NULL, resource_type VARCHAR(100) NOT NULL, resource_id VARCHAR(255) DEFAULT NULL, metadata JSON NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, previous_hash VARCHAR(64) NOT NULL, hash VARCHAR(64) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D62F2858D1B862B8 ON audit_logs (hash)');
        $this->addSql('CREATE TABLE notifications (id VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, message TEXT NOT NULL, type VARCHAR(50) NOT NULL, read_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id VARCHAR(36) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE permissions (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2DEDCC6F5E237E06 ON permissions (name)');
        $this->addSql('CREATE TABLE refresh_tokens (id VARCHAR(36) NOT NULL, token TEXT NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, revoked BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_id VARCHAR(36) DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9BACE7E15F37A13B ON refresh_tokens (token)');
        $this->addSql('CREATE INDEX IDX_9BACE7E1A76ED395 ON refresh_tokens (user_id)');
        $this->addSql('CREATE TABLE roles (id VARCHAR(36) NOT NULL, name VARCHAR(100) NOT NULL, description TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B63E2EC75E237E06 ON roles (name)');
        $this->addSql('CREATE TABLE role_permissions (role_id VARCHAR(36) NOT NULL, permission_id VARCHAR(36) NOT NULL, PRIMARY KEY (role_id, permission_id))');
        $this->addSql('CREATE INDEX IDX_1FBA94E6D60322AC ON role_permissions (role_id)');
        $this->addSql('CREATE INDEX IDX_1FBA94E6FED90CCA ON role_permissions (permission_id)');
        $this->addSql('CREATE TABLE users (id VARCHAR(36) NOT NULL, email VARCHAR(255) NOT NULL, password_hash VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, avatar_url VARCHAR(500) DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE TABLE user_roles (user_id VARCHAR(36) NOT NULL, role_id VARCHAR(36) NOT NULL, PRIMARY KEY (user_id, role_id))');
        $this->addSql('CREATE INDEX IDX_54FCD59FA76ED395 ON user_roles (user_id)');
        $this->addSql('CREATE INDEX IDX_54FCD59FD60322AC ON user_roles (role_id)');
        $this->addSql('CREATE TABLE user_permissions (user_id VARCHAR(36) NOT NULL, permission_id VARCHAR(36) NOT NULL, PRIMARY KEY (user_id, permission_id))');
        $this->addSql('CREATE INDEX IDX_84F605FAA76ED395 ON user_permissions (user_id)');
        $this->addSql('CREATE INDEX IDX_84F605FAFED90CCA ON user_permissions (permission_id)');
        $this->addSql('ALTER TABLE refresh_tokens ADD CONSTRAINT FK_9BACE7E1A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6D60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('ALTER TABLE role_permissions ADD CONSTRAINT FK_1FBA94E6FED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_roles ADD CONSTRAINT FK_54FCD59FD60322AC FOREIGN KEY (role_id) REFERENCES roles (id)');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE user_permissions ADD CONSTRAINT FK_84F605FAFED90CCA FOREIGN KEY (permission_id) REFERENCES permissions (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_tokens DROP CONSTRAINT FK_9BACE7E1A76ED395');
        $this->addSql('ALTER TABLE role_permissions DROP CONSTRAINT FK_1FBA94E6D60322AC');
        $this->addSql('ALTER TABLE role_permissions DROP CONSTRAINT FK_1FBA94E6FED90CCA');
        $this->addSql('ALTER TABLE user_roles DROP CONSTRAINT FK_54FCD59FA76ED395');
        $this->addSql('ALTER TABLE user_roles DROP CONSTRAINT FK_54FCD59FD60322AC');
        $this->addSql('ALTER TABLE user_permissions DROP CONSTRAINT FK_84F605FAA76ED395');
        $this->addSql('ALTER TABLE user_permissions DROP CONSTRAINT FK_84F605FAFED90CCA');
        $this->addSql('DROP TABLE audit_logs');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE permissions');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE roles');
        $this->addSql('DROP TABLE role_permissions');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_roles');
        $this->addSql('DROP TABLE user_permissions');
    }
}
