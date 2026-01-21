<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119212851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE establishments DROP bacType, DROP filieresAcceptees, DROP combinaisonsBacMission, CHANGE gratuit gratuit TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE secteurs ADD keywords JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE establishments ADD bacType VARCHAR(20) DEFAULT NULL, ADD filieresAcceptees JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', ADD combinaisonsBacMission JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', CHANGE gratuit gratuit TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE secteurs DROP keywords');
    }
}
