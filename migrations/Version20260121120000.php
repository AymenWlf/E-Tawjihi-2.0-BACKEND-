<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add planReussiteSteps field to user_profile table
 */
final class Version20260121120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add planReussiteSteps JSON field to user_profile table for storing plan de réussite steps completion';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_profile ADD plan_reussite_steps JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_profile DROP plan_reussite_steps');
    }
}
