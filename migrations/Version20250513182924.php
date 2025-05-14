<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250513182924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation_detail RENAME INDEX idx_d6e3f8a456c5646 TO IDX_E4794A64456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD responses JSON DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation_detail RENAME INDEX idx_e4794a64456c5646 TO IDX_D6E3F8A456C5646
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP responses
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
