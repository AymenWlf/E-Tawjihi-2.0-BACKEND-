<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add type_lycee field to user_profile (Public / Privé)
 */
final class Version20260126130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add type_lycee column to user_profile for choice Public or Privé';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_profile ADD type_lycee VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_profile DROP type_lycee');
    }
}
