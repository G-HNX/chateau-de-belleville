<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219150841 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("ALTER TABLE food_pairing ADD COLUMN icon VARCHAR(10) NOT NULL DEFAULT '🍽️'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__food_pairing AS SELECT id, name, slug, description FROM food_pairing');
        $this->addSql('DROP TABLE food_pairing');
        $this->addSql('CREATE TABLE food_pairing (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('INSERT INTO food_pairing (id, name, slug, description) SELECT id, name, slug, description FROM __temp__food_pairing');
        $this->addSql('DROP TABLE __temp__food_pairing');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C78930C4989D9B62 ON food_pairing (slug)');
    }
}
