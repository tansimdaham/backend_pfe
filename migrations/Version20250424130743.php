<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250424130743 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_A412FA9268998B84 ON quiz
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE main_surface main_surface TINYINT(1) NOT NULL, CHANGE vitesse vitesse TINYINT(1) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE main_surface main_surface VARCHAR(50) NOT NULL, CHANGE vitesse vitesse VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_A412FA9268998B84 ON quiz (idmodule)
        SQL);
    }
}
