<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110160217 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Version20260110155756 a déjà ajouté secteurs_ids, on ajoute seulement filieres_ids
        // Vérifier si la colonne existe déjà avant de l'ajouter
        $connection = $this->connection;
        $schemaManager = $connection->createSchemaManager();
        $tableExists = $schemaManager->tablesExist(['establishments']);
        
        if ($tableExists) {
            $columns = $schemaManager->listTableColumns('establishments');
            $hasFilieresIds = false;
            
            foreach ($columns as $column) {
                if ($column->getName() === 'filieres_ids') {
                    $hasFilieresIds = true;
                    break;
                }
            }
            
            if (!$hasFilieresIds) {
                $this->addSql('ALTER TABLE establishments ADD filieres_ids JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
            }
        } else {
            $this->addSql('ALTER TABLE establishments ADD filieres_ids JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // On supprime seulement filieres_ids (secteurs_ids sera supprimé par Version20260110155756)
        $this->addSql('ALTER TABLE establishments DROP filieres_ids');
    }
}
