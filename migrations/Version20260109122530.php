<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260109122530 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE secteurs (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, icon VARCHAR(100) DEFAULT NULL, image VARCHAR(500) DEFAULT NULL, soft_skills JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', personnalites JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', bacs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', type_bacs JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', avantages JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', inconvenients JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', metiers JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', salaire_min NUMERIC(10, 2) DEFAULT NULL, salaire_max NUMERIC(10, 2) DEFAULT NULL, is_activate TINYINT(1) NOT NULL, status VARCHAR(50) NOT NULL, is_complet TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE secteurs');
    }
}
