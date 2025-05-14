<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501165930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD idmodule VARCHAR(50) NOT NULL, CHANGE quiz_id quiz_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD idmodule VARCHAR(50) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP idmodule, CHANGE quiz_id quiz_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP idmodule
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
