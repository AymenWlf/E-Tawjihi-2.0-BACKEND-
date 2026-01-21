<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260107130610 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace ville column with city_id relation in campus table';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne city_id existe déjà
        $hasCityId = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'campus' 
             AND COLUMN_NAME = 'city_id'"
        );
        
        // Étape 1: Créer une ville par défaut si elle n'existe pas
        $this->addSql("INSERT IGNORE INTO city (titre, longitude, latitude) VALUES ('Casablanca', -7.5898, 33.5731)");
        
        // Étape 2: Ajouter la colonne city_id seulement si elle n'existe pas
        if (!$hasCityId) {
            $this->addSql('ALTER TABLE campus ADD city_id INT DEFAULT NULL');
        }
        
        // Étape 3: Mettre à jour les campus existants avec la ville par défaut
        $defaultCityId = $this->connection->fetchOne("SELECT id FROM city WHERE titre = 'Casablanca' LIMIT 1");
        if ($defaultCityId) {
            // Mettre à jour les campus avec city_id NULL
            $this->addSql("UPDATE campus SET city_id = ? WHERE city_id IS NULL", [$defaultCityId]);
            // Mettre à jour les campus avec des city_id invalides
            $this->addSql("UPDATE campus SET city_id = ? WHERE city_id NOT IN (SELECT id FROM city)", [$defaultCityId]);
        }
        
        // Étape 4: Rendre la colonne obligatoire
        $this->addSql('ALTER TABLE campus MODIFY city_id INT NOT NULL');
        
        // Étape 5: Supprimer l'ancienne colonne ville si elle existe
        $hasVille = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'campus' 
             AND COLUMN_NAME = 'ville'"
        );
        if ($hasVille) {
            $this->addSql('ALTER TABLE campus DROP ville');
        }
        
        // Étape 6: Supprimer la contrainte existante si elle existe
        $hasConstraint = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'campus' 
             AND CONSTRAINT_NAME = 'FK_9D0968118BAC62AF'"
        );
        if ($hasConstraint) {
            $this->addSql('ALTER TABLE campus DROP FOREIGN KEY FK_9D0968118BAC62AF');
        }
        
        // Étape 7: Ajouter la contrainte de clé étrangère
        $this->addSql('ALTER TABLE campus ADD CONSTRAINT FK_9D0968118BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        
        // Étape 8: Créer l'index si nécessaire
        $hasIndex = $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.STATISTICS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = 'campus' 
             AND INDEX_NAME = 'IDX_9D0968118BAC62AF'"
        );
        if (!$hasIndex) {
        $this->addSql('CREATE INDEX IDX_9D0968118BAC62AF ON campus (city_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE campus DROP FOREIGN KEY FK_9D0968118BAC62AF');
        $this->addSql('DROP INDEX IDX_9D0968118BAC62AF ON campus');
        $this->addSql('ALTER TABLE campus ADD ville VARCHAR(100) NOT NULL, DROP city_id');
    }
}
