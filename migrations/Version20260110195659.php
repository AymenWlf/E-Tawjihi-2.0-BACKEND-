<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260110195659 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add view_count column to establishments and filieres tables for tracking views.';
    }

    public function up(Schema $schema): void
    {
        // Add view_count column to establishments table
        $this->addSql('ALTER TABLE establishments ADD view_count INT DEFAULT 0 NOT NULL');

        // Add view_count column to filieres table
        $this->addSql('ALTER TABLE filieres ADD view_count INT DEFAULT 0 NOT NULL');

        // Note: articles table already has 'vues' column (checked in Article entity)
    }

    public function down(Schema $schema): void
    {
        // Remove view_count column from establishments table
        $this->addSql('ALTER TABLE establishments DROP view_count');

        // Remove view_count column from filieres table
        $this->addSql('ALTER TABLE filieres DROP view_count');
    }
}
