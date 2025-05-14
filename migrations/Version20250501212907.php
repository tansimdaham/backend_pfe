<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501212907 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make idmodule column NOT NULL in competence table';
    }

    public function up(Schema $schema): void
    {
        // Drop foreign key constraint first
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F68998B84
        SQL);

        // Update the column to NOT NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE competence CHANGE idmodule idmodule VARCHAR(50) NOT NULL
        SQL);

        // Recreate the foreign key constraint
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F68998B84 FOREIGN KEY (idmodule) REFERENCES quiz (idmodule)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Drop foreign key constraint first
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F68998B84
        SQL);

        // Revert the column to allow NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE competence CHANGE idmodule idmodule VARCHAR(50) DEFAULT NULL
        SQL);

        // Recreate the foreign key constraint
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F68998B84 FOREIGN KEY (idmodule) REFERENCES quiz (idmodule)
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
