<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109131902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE metiers (id INT AUTO_INCREMENT NOT NULL, secteur_id INT NOT NULL, nom VARCHAR(255) NOT NULL, nom_arabe VARCHAR(255) DEFAULT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, niveau_accessibilite VARCHAR(50) DEFAULT NULL, salaire_min NUMERIC(10, 2) DEFAULT NULL, salaire_max NUMERIC(10, 2) DEFAULT NULL, competences JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', formations JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', is_activate TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_FF51A00D989D9B62 (slug), INDEX IDX_FF51A00D9F7E4405 (secteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE metiers ADD CONSTRAINT FK_FF51A00D9F7E4405 FOREIGN KEY (secteur_id) REFERENCES secteurs (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE metiers DROP FOREIGN KEY FK_FF51A00D9F7E4405');
        $this->addSql('DROP TABLE metiers');
    }
}
