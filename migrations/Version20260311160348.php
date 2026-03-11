<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311160348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at, email_auth_code, two_factor_enabled FROM user');
        $this->addSql('DROP TABLE user');
        $this->addSql('CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, is_verified BOOLEAN NOT NULL, newsletter_opt_in BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, email_auth_code VARCHAR(10) DEFAULT NULL, two_factor_enabled BOOLEAN NOT NULL)');
        $this->addSql('INSERT INTO user (id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at, email_auth_code, two_factor_enabled) SELECT id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at, email_auth_code, two_factor_enabled FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at, email_auth_code, two_factor_enabled FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone VARCHAR(20) DEFAULT NULL, birth_date DATE DEFAULT NULL, is_verified BOOLEAN NOT NULL, newsletter_opt_in BOOLEAN NOT NULL, created_at DATETIME NOT NULL, last_login_at DATETIME DEFAULT NULL, email_auth_code VARCHAR(10) DEFAULT NULL, two_factor_enabled BOOLEAN DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at, email_auth_code, two_factor_enabled) SELECT id, email, roles, password, first_name, last_name, phone, birth_date, is_verified, newsletter_opt_in, created_at, last_login_at, email_auth_code, two_factor_enabled FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
    }
}
