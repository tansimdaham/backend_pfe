<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501223710 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9268998B84
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C9268998B84 FOREIGN KEY (idmodule) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F68998B84
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F68998B84 FOREIGN KEY (idmodule) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz RENAME INDEX unique_idmodule TO UNIQ_A412FA9259FED578
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9268998B84
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C9268998B84 FOREIGN KEY (idmodule) REFERENCES quiz (IDModule) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F68998B84
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F68998B84 FOREIGN KEY (idmodule) REFERENCES quiz (IDModule) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz RENAME INDEX uniq_a412fa9259fed578 TO UNIQUE_IDMODULE
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
