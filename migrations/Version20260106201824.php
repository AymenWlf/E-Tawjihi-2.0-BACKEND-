<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260106201824 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE filiere_campus (filiere_id INT NOT NULL, campus_id INT NOT NULL, INDEX IDX_A2C53E8E180AA129 (filiere_id), INDEX IDX_A2C53E8EAF5D55E1 (campus_id), PRIMARY KEY(filiere_id, campus_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE filiere_campus ADD CONSTRAINT FK_A2C53E8E180AA129 FOREIGN KEY (filiere_id) REFERENCES filieres (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE filiere_campus ADD CONSTRAINT FK_A2C53E8EAF5D55E1 FOREIGN KEY (campus_id) REFERENCES campus (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE filiere_campus DROP FOREIGN KEY FK_A2C53E8E180AA129');
        $this->addSql('ALTER TABLE filiere_campus DROP FOREIGN KEY FK_A2C53E8EAF5D55E1');
        $this->addSql('DROP TABLE filiere_campus');
    }
}
