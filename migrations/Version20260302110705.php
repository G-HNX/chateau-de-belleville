<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302110705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE newsletter_subscriber (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, subscribed_at DATETIME NOT NULL, unsubscribe_token VARCHAR(64) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_401562C3E7927C74 ON newsletter_subscriber (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_401562C3E0674361 ON newsletter_subscriber (unsubscribe_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE newsletter_subscriber');
    }
}
