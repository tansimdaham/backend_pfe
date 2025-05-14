<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501211814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C9268998B84 FOREIGN KEY (idmodule) REFERENCES quiz (idmodule)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_47CC8C9268998B84 ON action (idmodule)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence CHANGE idmodule idmodule VARCHAR(50) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F68998B84 FOREIGN KEY (idmodule) REFERENCES quiz (idmodule)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_94D4687F68998B84 ON competence (idmodule)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9268998B84
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_47CC8C9268998B84 ON action
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F68998B84
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_94D4687F68998B84 ON competence
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence CHANGE idmodule idmodule VARCHAR(50) NOT NULL
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
