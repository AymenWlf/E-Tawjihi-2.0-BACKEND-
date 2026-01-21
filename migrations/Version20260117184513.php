<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260117184513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne existe déjà avant de l'ajouter
        $this->addSql("
            SET @dbname = DATABASE();
            SET @tablename = 'establishments';
            SET @columnname = 'gratuit';
            SET @preparedStatement = (SELECT IF(
              (
                SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                WHERE
                  (table_name = @tablename)
                  AND (table_schema = @dbname)
                  AND (column_name = @columnname)
              ) > 0,
              'SELECT 1',
              CONCAT('ALTER TABLE ', @tablename, ' ADD ', @columnname, ' TINYINT(1) DEFAULT 0 NOT NULL')
            ));
            PREPARE alterIfNotExists FROM @preparedStatement;
            EXECUTE alterIfNotExists;
            DEALLOCATE PREPARE alterIfNotExists;
        ");
    }

    public function down(Schema $schema): void
    {
        // Vérifier si la colonne existe avant de la supprimer
        $this->addSql('ALTER TABLE establishments DROP gratuit');
    }
}
