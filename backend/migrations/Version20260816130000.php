<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260816130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image_filename to products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products ADD image_filename VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE products DROP image_filename');
    }
}
