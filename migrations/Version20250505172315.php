<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505172315 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant DROP FOREIGN KEY FK_C4EB462E35920590
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_C4EB462E35920590 ON apprenant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant CHANGE certificta_id certificat_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant ADD CONSTRAINT FK_C4EB462EFA55BACF FOREIGN KEY (certificat_id) REFERENCES certificat (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C4EB462EFA55BACF ON apprenant (certificat_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression ADD apprenant_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression ADD CONSTRAINT FK_D5B25073C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D5B25073C5697D6D ON progression (apprenant_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073C5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D5B25073C5697D6D ON progression
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression DROP apprenant_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant DROP FOREIGN KEY FK_C4EB462EFA55BACF
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_C4EB462EFA55BACF ON apprenant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant CHANGE certificat_id certificta_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant ADD CONSTRAINT FK_C4EB462E35920590 FOREIGN KEY (certificta_id) REFERENCES certificat (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C4EB462E35920590 ON apprenant (certificta_id)
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
