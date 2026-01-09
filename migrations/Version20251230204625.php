<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251230204625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_EMAIL ON user');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(180) DEFAULT NULL, CHANGE phone phone VARCHAR(20) NOT NULL');
        $this->addSql('DROP INDEX uniq_8d93d649444f97dd ON user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PHONE ON user (phone)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` CHANGE email email VARCHAR(180) NOT NULL, CHANGE phone phone VARCHAR(20) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON `user` (email)');
        $this->addSql('DROP INDEX uniq_identifier_phone ON `user`');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649444F97DD ON `user` (phone)');
    }
}
