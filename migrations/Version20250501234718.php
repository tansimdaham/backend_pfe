<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501234718 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action CHANGE quiz_id quiz_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence CHANGE quiz_id quiz_id INT NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action CHANGE quiz_id quiz_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence CHANGE quiz_id quiz_id INT DEFAULT NULL
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
