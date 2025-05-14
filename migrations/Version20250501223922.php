<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501223922 extends AbstractMigration
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
            ALTER TABLE quiz RENAME INDEX unique_idmodule TO UNIQ_A412FA9268998B84
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9268998B84
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz RENAME INDEX uniq_a412fa9268998b84 TO UNIQUE_IDMODULE
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
