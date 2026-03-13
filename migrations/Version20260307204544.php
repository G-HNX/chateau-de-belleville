<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260307204544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__reservation AS SELECT id, reference, status, first_name, last_name, email, phone, number_of_participants, message, admin_notes, created_at, confirmed_at, user_id, slot_id, price_per_person_in_cents FROM reservation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, number_of_participants SMALLINT NOT NULL, message CLOB DEFAULT NULL, admin_notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, user_id INTEGER DEFAULT NULL, slot_id INTEGER NOT NULL, price_per_person_in_cents INTEGER NOT NULL, CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C8495559E5119C FOREIGN KEY (slot_id) REFERENCES tasting_slot (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO reservation (id, reference, status, first_name, last_name, email, phone, number_of_participants, message, admin_notes, created_at, confirmed_at, user_id, slot_id, price_per_person_in_cents) SELECT id, reference, status, first_name, last_name, email, phone, number_of_participants, message, admin_notes, created_at, confirmed_at, user_id, slot_id, price_per_person_in_cents FROM __temp__reservation');
        $this->addSql('DROP TABLE __temp__reservation');
        $this->addSql('CREATE INDEX idx_reservation_status ON reservation (status)');
        $this->addSql('CREATE INDEX idx_reservation_reference ON reservation (reference)');
        $this->addSql('CREATE INDEX IDX_42C8495559E5119C ON reservation (slot_id)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_42C84955AEA34913 ON reservation (reference)');
        $this->addSql('ALTER TABLE user ADD COLUMN email_auth_code VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN two_factor_enabled BOOLEAN NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__reservation AS SELECT id, reference, status, first_name, last_name, email, phone, number_of_participants, price_per_person_in_cents, message, admin_notes, created_at, confirmed_at, user_id, slot_id FROM reservation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, number_of_participants SMALLINT NOT NULL, price_per_person_in_cents INTEGER DEFAULT 0 NOT NULL, message CLOB DEFAULT NULL, admin_notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL, user_id INTEGER DEFAULT NULL, slot_id INTEGER NOT NULL, CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_42C8495559E5119C FOREIGN KEY (slot_id) REFERENCES tasting_slot (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO reservation (id, reference, status, first_name, last_name, email, phone, number_of_participants, price_per_person_in_cents, message, admin_notes, created_at, confirmed_at, user_id, slot_id) SELECT id, reference, status, first_name, last_name, email, phone, number_of_participants, price_per_person_in_cents, message, admin_notes, created_at, confirmed_at, user_id, slot_id FROM __temp__reservation');
        $this->addSql('DROP TABLE __temp__reservation');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_42C84955AEA34913 ON reservation (reference)');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('CREATE INDEX IDX_42C8495559E5119C ON reservation (slot_id)');
        $this->addSql('CREATE INDEX idx_reservation_reference ON reservation (reference)');
        $this->addSql('CREATE INDEX idx_reservation_status ON reservation (status)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, is_verified BOOLEAN NOT NULL, newsletter_opt_in BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at) SELECT id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }
}
