<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add afficherDansTest field to secteurs table
 */
final class Version20260121130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add afficherDansTest boolean field to secteurs table for controlling display in career test and report';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE secteurs ADD afficher_dans_test TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE secteurs DROP afficher_dans_test');
    }
}
