<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260816121548 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at to customers, orders and products, for date-range filtering';
    }

    public function up(Schema $schema): void
    {
        // Backfill existing rows with now() at add-time, then drop the default so the
        // application (which always sets createdAt itself) is the only future source of
        // truth for the column.
        $this->addSql('ALTER TABLE customers ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT now()');
        $this->addSql('ALTER TABLE customers ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE orders ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT now()');
        $this->addSql('ALTER TABLE orders ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE products ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT now()');
        $this->addSql('ALTER TABLE products ALTER created_at DROP DEFAULT');
        $this->addSql('ALTER TABLE users ALTER preferred_language DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customers DROP created_at');
        $this->addSql('ALTER TABLE orders DROP created_at');
        $this->addSql('ALTER TABLE products DROP created_at');
        $this->addSql('ALTER TABLE users ALTER preferred_language SET DEFAULT \'es\'');
    }
}
