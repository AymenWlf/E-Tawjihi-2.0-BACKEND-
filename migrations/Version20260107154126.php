<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107154126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE establishments ADD duree_etudes_min INT DEFAULT NULL, ADD duree_etudes_max INT DEFAULT NULL, ADD frais_scolarite_min NUMERIC(10, 2) DEFAULT NULL, ADD frais_scolarite_max NUMERIC(10, 2) DEFAULT NULL, ADD frais_inscription_min NUMERIC(10, 2) DEFAULT NULL, ADD frais_inscription_max NUMERIC(10, 2) DEFAULT NULL, ADD diplomes_delivres JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE establishments DROP duree_etudes_min, DROP duree_etudes_max, DROP frais_scolarite_min, DROP frais_scolarite_max, DROP frais_inscription_min, DROP frais_inscription_max, DROP diplomes_delivres');
    }
}
