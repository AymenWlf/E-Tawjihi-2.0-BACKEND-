<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260115180937 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Vérifier si la table qualification_requests existe déjà
        $tableExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'qualification_requests'"
        )->fetchOne();
        
        if (!$tableExists) {
            $this->addSql('CREATE TABLE qualification_requests (id INT AUTO_INCREMENT NOT NULL, establishment_id INT DEFAULT NULL, filiere_id INT DEFAULT NULL, ville_id INT DEFAULT NULL, source VARCHAR(255) NOT NULL, tuteur_eleve VARCHAR(20) NOT NULL, nom_prenom VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, type_ecole VARCHAR(50) DEFAULT NULL, niveau_etude VARCHAR(100) DEFAULT NULL, filiere_bac VARCHAR(255) DEFAULT NULL, pret_payer VARCHAR(10) NOT NULL, besoin_orientation TINYINT(1) NOT NULL, besoin_test TINYINT(1) NOT NULL, besoin_notification TINYINT(1) NOT NULL, besoin_inscription TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_processed TINYINT(1) NOT NULL, INDEX IDX_2DA5F18B8565851 (establishment_id), INDEX IDX_2DA5F18B180AA129 (filiere_id), INDEX IDX_2DA5F18BA73F0036 (ville_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            
            // Vérifier si les contraintes existent déjà avant de les ajouter
            $constraintExists = $this->connection->executeQuery(
                "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'qualification_requests' AND constraint_name = 'FK_2DA5F18B8565851'"
            )->fetchOne();
            
            if (!$constraintExists) {
                $this->addSql('ALTER TABLE qualification_requests ADD CONSTRAINT FK_2DA5F18B8565851 FOREIGN KEY (establishment_id) REFERENCES establishments (id) ON DELETE SET NULL');
            }
            
            $constraintExists = $this->connection->executeQuery(
                "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'qualification_requests' AND constraint_name = 'FK_2DA5F18B180AA129'"
            )->fetchOne();
            
            if (!$constraintExists) {
                $this->addSql('ALTER TABLE qualification_requests ADD CONSTRAINT FK_2DA5F18B180AA129 FOREIGN KEY (filiere_id) REFERENCES filieres (id) ON DELETE SET NULL');
            }
            
            $constraintExists = $this->connection->executeQuery(
                "SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema = DATABASE() AND table_name = 'qualification_requests' AND constraint_name = 'FK_2DA5F18BA73F0036'"
            )->fetchOne();
            
            if (!$constraintExists) {
                $this->addSql('ALTER TABLE qualification_requests ADD CONSTRAINT FK_2DA5F18BA73F0036 FOREIGN KEY (ville_id) REFERENCES city (id) ON DELETE SET NULL');
            }
        }
        $this->addSql('ALTER TABLE establishment_answers CHANGE is_approved is_approved TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE establishment_questions CHANGE is_approved is_approved TINYINT(1) NOT NULL');
        
        // Vérifier si la colonne universite_text existe avant de la supprimer
        $columnExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'establishments' AND column_name = 'universite_text'"
        )->fetchOne();
        
        // Vérifier si les colonnes de bourses existent déjà
        $boursesColumnExists = $this->connection->executeQuery(
            "SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'establishments' AND column_name = 'bourses_disponibles'"
        )->fetchOne();
        
        if ($boursesColumnExists) {
            // Les colonnes existent déjà, on ne fait rien
        } else {
            // Construire la requête ALTER TABLE
            $alterSql = 'ALTER TABLE establishments ADD bourses_disponibles TINYINT(1) NOT NULL, ADD bourse_min NUMERIC(10, 2) DEFAULT NULL, ADD bourse_max NUMERIC(10, 2) DEFAULT NULL, ADD types_bourse JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'';
            if ($columnExists) {
                $alterSql .= ', DROP universite_text';
            }
            $alterSql .= ', CHANGE view_count view_count INT NOT NULL';
            $this->addSql($alterSql);
        }
        
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
        $this->addSql('ALTER TABLE establishments ADD universite_text VARCHAR(255) DEFAULT NULL, DROP bourses_disponibles, DROP bourse_min, DROP bourse_max, DROP types_bourse, CHANGE view_count view_count INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE filieres CHANGE view_count view_count INT DEFAULT 0 NOT NULL');
    }
}
