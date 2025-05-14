<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501222843 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_47CC8C92CCB495C ON action
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_94D4687FCCB495C ON competence
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_47CC8C92CCB495C ON action (idmodule)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_94D4687FCCB495C ON competence (idmodule)
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
