<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250508172525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD apprenant_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1323A575C5697D6D ON evaluation (apprenant_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie CHANGE sent_by_formateur sent_by_formateur TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575C5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1323A575C5697D6D ON evaluation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP apprenant_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie CHANGE sent_by_formateur sent_by_formateur TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
