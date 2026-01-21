<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110224452 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add universite_id foreign key to establishments table and migrate data from universite text field';
    }

    public function up(Schema $schema): void
    {
        $connection = $this->connection;
        
        // Check current state using direct queries
        $universiteExists = $connection->fetchOne("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'establishments' 
            AND COLUMN_NAME = 'universite'
        ");
        
        $universiteTextExists = $connection->fetchOne("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'establishments' 
            AND COLUMN_NAME = 'universite_text'
        ");
        
        $universiteIdExists = $connection->fetchOne("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'establishments' 
            AND COLUMN_NAME = 'universite_id'
        ");
        
        // Step 1: Rename universite to universite_text if needed
        if ($universiteExists > 0 && $universiteTextExists == 0) {
            $this->addSql('ALTER TABLE establishments CHANGE universite universite_text VARCHAR(255) DEFAULT NULL');
        } elseif ($universiteTextExists == 0 && $universiteExists == 0) {
            // If neither exists, create universite_text
            $this->addSql('ALTER TABLE establishments ADD universite_text VARCHAR(255) DEFAULT NULL');
        }
        // If universite_text already exists, skip this step
        
        // Step 2: Add universite_id column if it doesn't exist
        if ($universiteIdExists == 0) {
            $this->addSql('ALTER TABLE establishments ADD universite_id INT DEFAULT NULL');
        }
        
        // Step 3: Check if foreign key exists
        $fkExists = $connection->fetchOne("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'establishments' 
            AND CONSTRAINT_NAME = 'FK_5C67EFC52A52F05F'
        ");
        
        // Add foreign key constraint if it doesn't exist
        if ($fkExists == 0) {
        $this->addSql('ALTER TABLE establishments ADD CONSTRAINT FK_5C67EFC52A52F05F FOREIGN KEY (universite_id) REFERENCES universites (id) ON DELETE SET NULL');
        }
        
        // Step 4: Check if index exists
        $idxExists = $connection->fetchOne("
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'establishments' 
            AND INDEX_NAME = 'IDX_5C67EFC52A52F05F'
        ");
        
        // Create index if it doesn't exist
        if ($idxExists == 0) {
        $this->addSql('CREATE INDEX IDX_5C67EFC52A52F05F ON establishments (universite_id)');
        }
        
        // Step 5: Migrate existing data from universite_text to universite_id (only if both columns exist)
        if ($universiteTextExists > 0 && $universiteIdExists > 0) {
            $this->addSql("
                UPDATE establishments e
                INNER JOIN universites u ON e.universite_text = u.nom
                SET e.universite_id = u.id
                WHERE e.universite_text IS NOT NULL AND e.universite_text != '' AND (e.universite_id IS NULL OR e.universite_id = 0)
            ");
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE establishments DROP FOREIGN KEY FK_5C67EFC52A52F05F');
        $this->addSql('DROP INDEX IDX_5C67EFC52A52F05F ON establishments');
        $this->addSql('ALTER TABLE establishments DROP universite_id, CHANGE view_count view_count INT DEFAULT 0 NOT NULL, CHANGE universite_text universite VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE filieres CHANGE view_count view_count INT DEFAULT 0 NOT NULL');
    }
}
