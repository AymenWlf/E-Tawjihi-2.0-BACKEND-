<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260101205834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE test_answer (id INT AUTO_INCREMENT NOT NULL, test_session_id INT NOT NULL, question_key VARCHAR(255) NOT NULL, answer_data JSON NOT NULL COMMENT \'(DC2Type:json)\', answered_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', step_number INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_4D044D0B1A0C5AE6 (test_session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE test_session (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, test_type VARCHAR(255) NOT NULL, started_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', completed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', duration INT DEFAULT NULL, language VARCHAR(10) NOT NULL, total_questions INT DEFAULT NULL, metadata JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', current_step JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', is_completed TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_C05011CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE test_answer ADD CONSTRAINT FK_4D044D0B1A0C5AE6 FOREIGN KEY (test_session_id) REFERENCES test_session (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE test_session ADD CONSTRAINT FK_C05011CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE test_answer DROP FOREIGN KEY FK_4D044D0B1A0C5AE6');
        $this->addSql('ALTER TABLE test_session DROP FOREIGN KEY FK_C05011CA76ED395');
        $this->addSql('DROP TABLE test_answer');
        $this->addSql('DROP TABLE test_session');
    }
}
