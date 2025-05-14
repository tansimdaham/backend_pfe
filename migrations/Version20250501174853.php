<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501174853 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppression des colonnes quiz_id dans les tables competence et action pour utiliser uniquement idmodule';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_47CC8C92853CD175 ON action
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP quiz_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F853CD175
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_94D4687F853CD175 ON competence
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP quiz_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD quiz_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_47CC8C92853CD175 ON action (quiz_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD quiz_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_94D4687F853CD175 ON competence (quiz_id)
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
