<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260104231148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE campus (id INT AUTO_INCREMENT NOT NULL, establishment_id INT NOT NULL, nom VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, quartier VARCHAR(100) DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, code_postal VARCHAR(20) DEFAULT NULL, telephone VARCHAR(50) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, map_url LONGTEXT DEFAULT NULL, ordre INT DEFAULT NULL, INDEX IDX_9D0968118565851 (establishment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE establishments (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, sigle VARCHAR(50) NOT NULL, nom_arabe VARCHAR(255) DEFAULT NULL, type VARCHAR(50) NOT NULL, ville VARCHAR(100) NOT NULL, villes JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', pays VARCHAR(100) DEFAULT NULL, universite VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(500) DEFAULT NULL, image_couverture VARCHAR(500) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, telephone VARCHAR(50) DEFAULT NULL, site_web VARCHAR(500) DEFAULT NULL, adresse LONGTEXT DEFAULT NULL, code_postal VARCHAR(20) DEFAULT NULL, facebook VARCHAR(500) DEFAULT NULL, instagram VARCHAR(500) DEFAULT NULL, twitter VARCHAR(500) DEFAULT NULL, linkedin VARCHAR(500) DEFAULT NULL, youtube VARCHAR(500) DEFAULT NULL, nb_etudiants INT DEFAULT NULL, nb_filieres INT DEFAULT NULL, annee_creation INT DEFAULT NULL, accreditation_etat TINYINT(1) NOT NULL, concours TINYINT(1) NOT NULL, echange_international TINYINT(1) NOT NULL, annees_etudes INT DEFAULT NULL, bac_obligatoire TINYINT(1) NOT NULL, slug VARCHAR(255) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords LONGTEXT DEFAULT NULL, og_image VARCHAR(500) DEFAULT NULL, canonical_url VARCHAR(500) DEFAULT NULL, schema_type VARCHAR(100) DEFAULT NULL, no_index TINYINT(1) NOT NULL, is_active TINYINT(1) NOT NULL, is_recommended TINYINT(1) NOT NULL, is_sponsored TINYINT(1) NOT NULL, is_featured TINYINT(1) NOT NULL, video_url VARCHAR(500) DEFAULT NULL, documents JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', campus JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', status VARCHAR(50) DEFAULT NULL, is_complet TINYINT(1) NOT NULL, has_detail_page TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_5C67EFC5989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campus ADD CONSTRAINT FK_9D0968118565851 FOREIGN KEY (establishment_id) REFERENCES establishments (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campus DROP FOREIGN KEY FK_9D0968118565851');
        $this->addSql('DROP TABLE campus');
        $this->addSql('DROP TABLE establishments');
    }
}
