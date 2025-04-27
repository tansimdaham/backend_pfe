<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423144256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD quiz_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1323A575853CD175 ON evaluation (quiz_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92456C5646
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A412FA92456C5646 ON quiz
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP evaluation_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575853CD175
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1323A575853CD175 ON evaluation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP quiz_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD evaluation_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A412FA92456C5646 ON quiz (evaluation_id)
        SQL);
    }
}
