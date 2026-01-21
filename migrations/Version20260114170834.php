<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260114170834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE establishment_answers (id INT AUTO_INCREMENT NOT NULL, question_id INT NOT NULL, user_id INT NOT NULL, content LONGTEXT NOT NULL, likes INT NOT NULL, is_verified TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, is_approved TINYINT(1) NOT NULL, INDEX IDX_2DF7B3231E27F6BF (question_id), INDEX IDX_2DF7B323A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE establishment_questions (id INT AUTO_INCREMENT NOT NULL, establishment_id INT NOT NULL, user_id INT NOT NULL, content LONGTEXT NOT NULL, likes INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, is_active TINYINT(1) NOT NULL, is_approved TINYINT(1) NOT NULL, INDEX IDX_42407CA68565851 (establishment_id), INDEX IDX_42407CA6A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE establishment_answers ADD CONSTRAINT FK_2DF7B3231E27F6BF FOREIGN KEY (question_id) REFERENCES establishment_questions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE establishment_answers ADD CONSTRAINT FK_2DF7B323A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE establishment_questions ADD CONSTRAINT FK_42407CA68565851 FOREIGN KEY (establishment_id) REFERENCES establishments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE establishment_questions ADD CONSTRAINT FK_42407CA6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        // Ajouter les colonnes is_approved si elles n'existent pas déjà
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = \'establishment_questions\' AND column_name = \'is_approved\')');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, \'ALTER TABLE establishment_questions ADD is_approved TINYINT(1) NOT NULL DEFAULT 0\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
        $this->addSql('SET @exist := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = \'establishment_answers\' AND column_name = \'is_approved\')');
        $this->addSql('SET @sqlstmt := IF(@exist = 0, \'ALTER TABLE establishment_answers ADD is_approved TINYINT(1) NOT NULL DEFAULT 0\', \'SELECT 1\')');
        $this->addSql('PREPARE stmt FROM @sqlstmt');
        $this->addSql('EXECUTE stmt');
        $this->addSql('DEALLOCATE PREPARE stmt');
    }

    public function down(Schema $schema): void
    {
        // Supprimer les colonnes is_approved
        $this->addSql('ALTER TABLE establishment_questions DROP COLUMN IF EXISTS is_approved');
        $this->addSql('ALTER TABLE establishment_answers DROP COLUMN IF EXISTS is_approved');
    }
}
