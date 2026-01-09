<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106180422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE filieres (id INT AUTO_INCREMENT NOT NULL, establishment_id INT NOT NULL, nom VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, image_couverture VARCHAR(500) DEFAULT NULL, diplome VARCHAR(100) DEFAULT NULL, langue_etudes VARCHAR(50) DEFAULT NULL, frais_scolarite NUMERIC(10, 2) DEFAULT NULL, nombre_annees VARCHAR(50) DEFAULT NULL, type_ecole VARCHAR(50) DEFAULT NULL, bac_compatible TINYINT(1) NOT NULL, recommandee TINYINT(1) NOT NULL, metier JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_C97A115989D9B62 (slug), INDEX IDX_C97A1158565851 (establishment_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE filieres ADD CONSTRAINT FK_C97A1158565851 FOREIGN KEY (establishment_id) REFERENCES establishments (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filieres DROP FOREIGN KEY FK_C97A1158565851');
        $this->addSql('DROP TABLE filieres');
    }
}
