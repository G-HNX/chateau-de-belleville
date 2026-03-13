<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218230227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "order" ADD COLUMN customer_birth_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__order AS SELECT id, reference, status, customer_email, customer_first_name, customer_last_name, customer_phone, billing_address, shipping_address, subtotal_in_cents, shipping_cost_in_cents, tax_amount_in_cents, total_in_cents, stripe_payment_intent_id, tracking_number, carrier, admin_notes, customer_notes, created_at, paid_at, shipped_at, delivered_at, customer_id FROM "order"');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('CREATE TABLE "order" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, customer_email VARCHAR(180) NOT NULL, customer_first_name VARCHAR(100) DEFAULT NULL, customer_last_name VARCHAR(100) DEFAULT NULL, customer_phone VARCHAR(20) DEFAULT NULL, billing_address CLOB NOT NULL, shipping_address CLOB NOT NULL, subtotal_in_cents INTEGER NOT NULL, shipping_cost_in_cents INTEGER NOT NULL, tax_amount_in_cents INTEGER NOT NULL, total_in_cents INTEGER NOT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, tracking_number VARCHAR(100) DEFAULT NULL, carrier VARCHAR(100) DEFAULT NULL, admin_notes CLOB DEFAULT NULL, customer_notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, shipped_at DATETIME DEFAULT NULL, delivered_at DATETIME DEFAULT NULL, customer_id INTEGER DEFAULT NULL, CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO "order" (id, reference, status, customer_email, customer_first_name, customer_last_name, customer_phone, billing_address, shipping_address, subtotal_in_cents, shipping_cost_in_cents, tax_amount_in_cents, total_in_cents, stripe_payment_intent_id, tracking_number, carrier, admin_notes, customer_notes, created_at, paid_at, shipped_at, delivered_at, customer_id) SELECT id, reference, status, customer_email, customer_first_name, customer_last_name, customer_phone, billing_address, shipping_address, subtotal_in_cents, shipping_cost_in_cents, tax_amount_in_cents, total_in_cents, stripe_payment_intent_id, tracking_number, carrier, admin_notes, customer_notes, created_at, paid_at, shipped_at, delivered_at, customer_id FROM __temp__order');
        $this->addSql('DROP TABLE __temp__order');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398AEA34913 ON "order" (reference)');
        $this->addSql('CREATE INDEX IDX_F52993989395C3F3 ON "order" (customer_id)');
        $this->addSql('CREATE INDEX idx_order_reference ON "order" (reference)');
        $this->addSql('CREATE INDEX idx_order_status ON "order" (status)');
        $this->addSql('CREATE INDEX idx_order_date ON "order" (created_at)');
    }
}
