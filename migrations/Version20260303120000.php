<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout du snapshot de prix par personne sur la réservation.
 * Permet de conserver le tarif au moment de la réservation, indépendamment des futures modifications du prix de la dégustation.
 */
final class Version20260303120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout de price_per_person_in_cents sur la table reservation (snapshot du prix au moment de la réservation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation ADD COLUMN price_per_person_in_cents INTEGER NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // SQLite ne supporte pas DROP COLUMN — recréation de la table sans la colonne
        $this->addSql('CREATE TEMPORARY TABLE reservation_backup AS SELECT id, reference, user_id, slot_id, status, first_name, last_name, email, phone, number_of_participants, message, admin_notes, created_at, confirmed_at FROM reservation');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('CREATE TABLE reservation (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(20) NOT NULL, user_id INTEGER DEFAULT NULL, slot_id INTEGER NOT NULL, status VARCHAR(20) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(20) NOT NULL, number_of_participants SMALLINT NOT NULL, message CLOB DEFAULT NULL, admin_notes CLOB DEFAULT NULL, created_at DATETIME NOT NULL, confirmed_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO reservation SELECT * FROM reservation_backup');
        $this->addSql('DROP TABLE reservation_backup');
    }
}
