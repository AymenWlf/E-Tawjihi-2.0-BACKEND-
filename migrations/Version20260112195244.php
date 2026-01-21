<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112195244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Supprimer la table orientation_test et sa contrainte de clé étrangère
        $this->addSql('ALTER TABLE orientation_test DROP FOREIGN KEY FK_1BB3A0BDA76ED395');
        $this->addSql('DROP TABLE IF EXISTS orientation_test');
    }

    public function down(Schema $schema): void
    {
        // Recréer la table orientation_test (pour rollback)
        $this->addSql('CREATE TABLE orientation_test (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, personal_info JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', riasec JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', personality JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', aptitude JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', interests JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', career JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', constraints JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', languages JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', test_metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', analysis JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', current_step INT DEFAULT NULL, is_completed TINYINT(1) DEFAULT 0 NOT NULL, started_at DATETIME DEFAULT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_1BB3A0BDA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE orientation_test ADD CONSTRAINT FK_1BB3A0BDA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }
}
