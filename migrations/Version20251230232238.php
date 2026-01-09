<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230232238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_profile (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, ville_id INT DEFAULT NULL, user_type VARCHAR(20) DEFAULT NULL, niveau VARCHAR(100) DEFAULT NULL, bac_type VARCHAR(20) DEFAULT NULL, filiere VARCHAR(255) DEFAULT NULL, specialite1 VARCHAR(100) DEFAULT NULL, specialite2 VARCHAR(100) DEFAULT NULL, specialite3 VARCHAR(100) DEFAULT NULL, diplome_en_cours VARCHAR(100) DEFAULT NULL, nom_etablissement VARCHAR(255) DEFAULT NULL, type_ecole_prefere JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', services_prefere JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', nom VARCHAR(255) DEFAULT NULL, prenom VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, date_naissance DATE DEFAULT NULL, genre VARCHAR(20) DEFAULT NULL, tuteur VARCHAR(20) DEFAULT NULL, nom_tuteur VARCHAR(255) DEFAULT NULL, prenom_tuteur VARCHAR(255) DEFAULT NULL, tel_tuteur VARCHAR(20) DEFAULT NULL, profession_tuteur VARCHAR(255) DEFAULT NULL, adresse_tuteur VARCHAR(500) DEFAULT NULL, consent_contact TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_D95AB405A76ED395 (user_id), INDEX IDX_D95AB405A73F0036 (ville_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user_profile ADD CONSTRAINT FK_D95AB405A73F0036 FOREIGN KEY (ville_id) REFERENCES city (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A76ED395');
        $this->addSql('ALTER TABLE user_profile DROP FOREIGN KEY FK_D95AB405A73F0036');
        $this->addSql('DROP TABLE user_profile');
    }
}
