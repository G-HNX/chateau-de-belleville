<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260219145117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE food_pairing (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C78930C4989D9B62 ON food_pairing (slug)');
        $this->addSql('CREATE TABLE wine_food_pairing (wine_id INTEGER NOT NULL, food_pairing_id INTEGER NOT NULL, PRIMARY KEY (wine_id, food_pairing_id), CONSTRAINT FK_5C6668E128A2BD76 FOREIGN KEY (wine_id) REFERENCES wine (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_5C6668E1BBC5F61C FOREIGN KEY (food_pairing_id) REFERENCES food_pairing (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_5C6668E128A2BD76 ON wine_food_pairing (wine_id)');
        $this->addSql('CREATE INDEX IDX_5C6668E1BBC5F61C ON wine_food_pairing (food_pairing_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__wine AS SELECT id, name, slug, vintage, short_description, description, price_in_cents, stock, alcohol_degree, serving_temperature, aging_potential, tasting_notes, terroir, volume_cl, is_active, is_featured, created_at, updated_at, category_id, appellation_id FROM wine');
        $this->addSql('DROP TABLE wine');
        $this->addSql('CREATE TABLE wine (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, vintage SMALLINT DEFAULT NULL, short_description VARCHAR(500) DEFAULT NULL, description CLOB DEFAULT NULL, price_in_cents INTEGER NOT NULL, stock INTEGER NOT NULL, alcohol_degree NUMERIC(3, 1) DEFAULT NULL, serving_temperature VARCHAR(50) DEFAULT NULL, aging_potential VARCHAR(100) DEFAULT NULL, tasting_notes CLOB DEFAULT NULL, terroir CLOB DEFAULT NULL, volume_cl SMALLINT NOT NULL, is_active BOOLEAN NOT NULL, is_featured BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, category_id INTEGER DEFAULT NULL, appellation_id INTEGER DEFAULT NULL, CONSTRAINT FK_560C646812469DE2 FOREIGN KEY (category_id) REFERENCES wine_category (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_560C64687CDE30DD FOREIGN KEY (appellation_id) REFERENCES appellation (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO wine (id, name, slug, vintage, short_description, description, price_in_cents, stock, alcohol_degree, serving_temperature, aging_potential, tasting_notes, terroir, volume_cl, is_active, is_featured, created_at, updated_at, category_id, appellation_id) SELECT id, name, slug, vintage, short_description, description, price_in_cents, stock, alcohol_degree, serving_temperature, aging_potential, tasting_notes, terroir, volume_cl, is_active, is_featured, created_at, updated_at, category_id, appellation_id FROM __temp__wine');
        $this->addSql('DROP TABLE __temp__wine');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_560C6468989D9B62 ON wine (slug)');
        $this->addSql('CREATE INDEX IDX_560C646812469DE2 ON wine (category_id)');
        $this->addSql('CREATE INDEX IDX_560C64687CDE30DD ON wine (appellation_id)');
        $this->addSql('CREATE INDEX idx_wine_slug ON wine (slug)');
        $this->addSql('CREATE INDEX idx_wine_active_featured ON wine (is_active, is_featured)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE food_pairing');
        $this->addSql('DROP TABLE wine_food_pairing');
        $this->addSql('ALTER TABLE wine ADD COLUMN food_pairings CLOB DEFAULT NULL');
    }
}
