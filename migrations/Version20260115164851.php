<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115164851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE qualification_requests (id INT AUTO_INCREMENT NOT NULL, establishment_id INT DEFAULT NULL, filiere_id INT DEFAULT NULL, ville_id INT DEFAULT NULL, source VARCHAR(255) NOT NULL, tuteur_eleve VARCHAR(20) NOT NULL, nom_prenom VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, type_ecole VARCHAR(50) DEFAULT NULL, niveau_etude VARCHAR(100) DEFAULT NULL, filiere_bac VARCHAR(255) DEFAULT NULL, pret_payer VARCHAR(10) NOT NULL, besoin_orientation TINYINT(1) NOT NULL, besoin_test TINYINT(1) NOT NULL, besoin_notification TINYINT(1) NOT NULL, besoin_inscription TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_processed TINYINT(1) NOT NULL, INDEX IDX_2DA5F18B8565851 (establishment_id), INDEX IDX_2DA5F18B180AA129 (filiere_id), INDEX IDX_2DA5F18BA73F0036 (ville_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE qualification_requests ADD CONSTRAINT FK_2DA5F18B8565851 FOREIGN KEY (establishment_id) REFERENCES establishments (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qualification_requests ADD CONSTRAINT FK_2DA5F18B180AA129 FOREIGN KEY (filiere_id) REFERENCES filieres (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE qualification_requests ADD CONSTRAINT FK_2DA5F18BA73F0036 FOREIGN KEY (ville_id) REFERENCES city (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE establishment_answers CHANGE is_approved is_approved TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE establishment_questions CHANGE is_approved is_approved TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE establishments DROP universite_text, CHANGE view_count view_count INT NOT NULL');
        $this->addSql('ALTER TABLE filieres CHANGE view_count view_count INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE qualification_requests DROP FOREIGN KEY FK_2DA5F18B8565851');
        $this->addSql('ALTER TABLE qualification_requests DROP FOREIGN KEY FK_2DA5F18B180AA129');
        $this->addSql('ALTER TABLE qualification_requests DROP FOREIGN KEY FK_2DA5F18BA73F0036');
        $this->addSql('DROP TABLE qualification_requests');
        $this->addSql('ALTER TABLE establishment_answers CHANGE is_approved is_approved TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE establishment_questions CHANGE is_approved is_approved TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE establishments ADD universite_text VARCHAR(255) DEFAULT NULL, CHANGE view_count view_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE filieres CHANGE view_count view_count INT DEFAULT 0 NOT NULL');
    }
}
