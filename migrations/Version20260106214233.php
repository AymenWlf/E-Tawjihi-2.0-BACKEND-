<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106214233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filieres ADD meta_title VARCHAR(255) DEFAULT NULL, ADD meta_description LONGTEXT DEFAULT NULL, ADD meta_keywords LONGTEXT DEFAULT NULL, ADD og_image VARCHAR(500) DEFAULT NULL, ADD canonical_url VARCHAR(500) DEFAULT NULL, ADD schema_type VARCHAR(100) DEFAULT NULL, ADD no_index TINYINT(1) NOT NULL, ADD is_sponsored TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filieres DROP meta_title, DROP meta_description, DROP meta_keywords, DROP og_image, DROP canonical_url, DROP schema_type, DROP no_index, DROP is_sponsored');
    }
}
