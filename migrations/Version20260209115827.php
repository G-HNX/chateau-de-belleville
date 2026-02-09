<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260209115827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, label VARCHAR(100) DEFAULT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, street VARCHAR(255) NOT NULL, complement VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) NOT NULL, city VARCHAR(100) NOT NULL, country VARCHAR(2) NOT NULL, phone VARCHAR(20) DEFAULT NULL, is_default_shipping BOOLEAN NOT NULL, is_default_billing BOOLEAN NOT NULL, user_id INTEGER NOT NULL, CONSTRAINT FK_D4E6F81A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_D4E6F81A76ED395 ON address (user_id)');
        $this->addSql('CREATE TABLE appellation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, region VARCHAR(100) DEFAULT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_187A5B98989D9B62 ON appellation (slug)');
        $this->addSql('CREATE TABLE cart (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, session_id VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, user_id INTEGER DEFAULT NULL, CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA388B7613FECDF ON cart (session_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BA388B7A76ED395 ON cart (user_id)');
        $this->addSql('CREATE TABLE cart_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity SMALLINT NOT NULL, cart_id INTEGER NOT NULL, wine_id INTEGER NOT NULL, CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F0FE252728A2BD76 FOREIGN KEY (wine_id) REFERENCES wine (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_F0FE25271AD5CDBF ON cart_item (cart_id)');
        $this->addSql('CREATE INDEX IDX_F0FE252728A2BD76 ON cart_item (wine_id)');
        $this->addSql('CREATE TABLE grape_variety (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ECDE2267989D9B62 ON grape_variety (slug)');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_first_name VARCHAR(100) DEFAULT NULL, customer_last_name VARCHAR(100) DEFAULT NULL, customer_phone VARCHAR(20) DEFAULT NULL, billing_address CLOB NOT NULL, shipping_address CLOB NOT NULL, subtotal_in_cents INTEGER NOT NULL, shipping_cost_in_cents INTEGER NOT NULL, tax_amount_in_cents INTEGER NOT NULL, total_in_cents INTEGER NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, tracking_number VARCHAR(100) DEFAULT NULL, carrier VARCHAR(100) DEFAULT NULL, admin_notes CLOB DEFAULT NULL, customer_notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, shipped_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, customer_id INTEGER DEFAULT NULL, CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398AEA34913 ON "order" (reference)');
        $this->addSql('CREATE INDEX IDX_F52993989395C3F3 ON "order" (customer_id)');
        $this->addSql('CREATE INDEX idx_order_reference ON "order" (reference)');
        $this->addSql('CREATE INDEX idx_order_status ON "order" (status)');
        $this->addSql('CREATE INDEX idx_order_date ON "order" (created_at)');
        $this->addSql('CREATE TABLE order_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, wine_name VARCHAR(255) NOT NULL, wine_vintage SMALLINT DEFAULT NULL, unit_price_in_cents INTEGER NOT NULL, quantity SMALLINT NOT NULL, order_id INTEGER NOT NULL, wine_id INTEGER DEFAULT NULL, CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_52EA1F0928A2BD76 FOREIGN KEY (wine_id) REFERENCES wine (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_52EA1F098D9F6D38 ON order_item (order_id)');
        $this->addSql('CREATE INDEX IDX_52EA1F0928A2BD76 ON order_item (wine_id)');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, number_of_participants SMALLINT NOT NULL, message CLOB DEFAULT NULL, admin_notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, user_id INTEGER DEFAULT NULL, slot_id INTEGER NOT NULL, CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C8495559E5119C FOREIGN KEY (slot_id) REFERENCES tasting_slot (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_42C84955AEA34913 ON reservation (reference)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('CREATE INDEX IDX_42C8495559E5119C ON reservation (slot_id)');
        $this->addSql('CREATE INDEX idx_reservation_reference ON reservation (reference)');
        $this->addSql('CREATE INDEX idx_reservation_status ON reservation (status)');
        $this->addSql('CREATE TABLE review (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, rating SMALLINT NOT NULL, title VARCHAR(255) DEFAULT NULL, content CLOB DEFAULT NULL, is_approved BOOLEAN NOT NULL, created_at DATETIME NOT NULL, user_id INTEGER NOT NULL, wine_id INTEGER NOT NULL, CONSTRAINT FK_794381C6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_794381C628A2BD76 FOREIGN KEY (wine_id) REFERENCES wine (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_794381C6A76ED395 ON review (user_id)');
        $this->addSql('CREATE INDEX IDX_794381C628A2BD76 ON review (wine_id)');
        $this->addSql('CREATE UNIQUE INDEX unique_user_wine_review ON review (user_id, wine_id)');
        $this->addSql('CREATE TABLE tasting (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, price_in_cents INTEGER NOT NULL, duration_minutes SMALLINT NOT NULL, max_participants SMALLINT NOT NULL, min_participants SMALLINT NOT NULL, is_active BOOLEAN NOT NULL, included_items CLOB DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_73621810989D9B62 ON tasting (slug)');
        $this->addSql('CREATE TABLE tasting_slot (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, date DATE NOT NULL, start_time TIME NOT NULL, available_spots SMALLINT NOT NULL, is_available BOOLEAN NOT NULL, tasting_id INTEGER NOT NULL, CONSTRAINT FK_4B278DF55BC0FE1E FOREIGN KEY (tasting_id) REFERENCES tasting (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_4B278DF55BC0FE1E ON tasting_slot (tasting_id)');
        $this->addSql('CREATE INDEX idx_slot_datetime ON tasting_slot (date, start_time)');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, is_verified BOOLEAN NOT NULL, newsletter_opt_in BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('CREATE TABLE wine (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, type VARCHAR(20) NOT NULL, vintage SMALLINT DEFAULT NULL, short_description VARCHAR(500) DEFAULT NULL, description CLOB DEFAULT NULL, price_in_cents INTEGER NOT NULL, stock INTEGER NOT NULL, alcohol_degree NUMERIC(3, 1) DEFAULT NULL, serving_temperature VARCHAR(50) DEFAULT NULL, aging_potential VARCHAR(100) DEFAULT NULL, food_pairings CLOB DEFAULT NULL, tasting_notes CLOB DEFAULT NULL, terroir CLOB DEFAULT NULL, volume_cl SMALLINT NOT NULL, is_active BOOLEAN NOT NULL, is_featured BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, category_id INTEGER DEFAULT NULL, appellation_id INTEGER DEFAULT NULL, CONSTRAINT FK_560C646812469DE2 FOREIGN KEY (category_id) REFERENCES wine_category (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_560C64687CDE30DD FOREIGN KEY (appellation_id) REFERENCES appellation (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_560C6468989D9B62 ON wine (slug)');
        $this->addSql('CREATE INDEX IDX_560C646812469DE2 ON wine (category_id)');
        $this->addSql('CREATE INDEX IDX_560C64687CDE30DD ON wine (appellation_id)');
        $this->addSql('CREATE INDEX idx_wine_slug ON wine (slug)');
        $this->addSql('CREATE INDEX idx_wine_active_featured ON wine (is_active, is_featured)');
        $this->addSql('CREATE INDEX idx_wine_type ON wine (type)');
        $this->addSql('CREATE TABLE wine_grape_variety (wine_id INTEGER NOT NULL, grape_variety_id INTEGER NOT NULL, PRIMARY KEY (wine_id, grape_variety_id), CONSTRAINT FK_A741197828A2BD76 FOREIGN KEY (wine_id) REFERENCES wine (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A7411978ED00A18A FOREIGN KEY (grape_variety_id) REFERENCES grape_variety (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A741197828A2BD76 ON wine_grape_variety (wine_id)');
        $this->addSql('CREATE INDEX IDX_A7411978ED00A18A ON wine_grape_variety (grape_variety_id)');
        $this->addSql('CREATE TABLE wine_category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(100) NOT NULL, slug VARCHAR(100) NOT NULL, description CLOB DEFAULT NULL, position SMALLINT NOT NULL, is_active BOOLEAN NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_86E150C2989D9B62 ON wine_category (slug)');
        $this->addSql('CREATE TABLE wine_image (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, alt_text VARCHAR(255) DEFAULT NULL, position SMALLINT NOT NULL, is_main BOOLEAN NOT NULL, wine_id INTEGER NOT NULL, CONSTRAINT FK_E5F759C328A2BD76 FOREIGN KEY (wine_id) REFERENCES wine (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_E5F759C328A2BD76 ON wine_image (wine_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE appellation');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE grape_variety');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE tasting');
        $this->addSql('DROP TABLE tasting_slot');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE wine');
        $this->addSql('DROP TABLE wine_grape_variety');
        $this->addSql('DROP TABLE wine_category');
        $this->addSql('DROP TABLE wine_image');
    }
}
