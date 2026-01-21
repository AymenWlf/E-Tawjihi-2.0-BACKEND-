<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109124846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE articles (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, contenu LONGTEXT DEFAULT NULL, image_couverture VARCHAR(500) DEFAULT NULL, categorie VARCHAR(100) DEFAULT NULL, categories JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', tags JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', auteur VARCHAR(255) DEFAULT NULL, date_publication DATETIME DEFAULT NULL, status VARCHAR(50) NOT NULL, featured TINYINT(1) NOT NULL, meta_title VARCHAR(255) DEFAULT NULL, meta_description LONGTEXT DEFAULT NULL, meta_keywords LONGTEXT DEFAULT NULL, og_image VARCHAR(500) DEFAULT NULL, og_title VARCHAR(255) DEFAULT NULL, og_description LONGTEXT DEFAULT NULL, canonical_url VARCHAR(500) DEFAULT NULL, schema_type VARCHAR(100) DEFAULT NULL, no_index TINYINT(1) NOT NULL, temps_lecture INT DEFAULT NULL, vues INT NOT NULL, is_activate TINYINT(1) NOT NULL, is_complet TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_BFDD3168989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE articles');
    }
}
