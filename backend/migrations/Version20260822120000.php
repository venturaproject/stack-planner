<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260822120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Require an owner for refresh and API client tokens';
    }

    public function up(Schema $schema): void
    {
        // Tokens without an owner cannot be authenticated or refreshed safely.
        $this->addSql('DELETE FROM refresh_tokens WHERE user_id IS NULL');
        $this->addSql('DELETE FROM api_client_tokens WHERE api_client_id IS NULL');
        $this->addSql('ALTER TABLE refresh_tokens ALTER user_id SET NOT NULL');
        $this->addSql('ALTER TABLE api_client_tokens ALTER api_client_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_tokens ALTER user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE api_client_tokens ALTER api_client_id DROP NOT NULL');
    }
}
