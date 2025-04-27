<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250424150708 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE action_nom_fr action_nom_fr VARCHAR(50) DEFAULT NULL, CHANGE action_nom_en action_nom_en VARCHAR(50) DEFAULT NULL, CHANGE action_categorie_fr action_categorie_fr VARCHAR(50) DEFAULT NULL, CHANGE action_categorie_en action_categorie_en VARCHAR(50) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE action_nom_fr action_nom_fr VARCHAR(50) NOT NULL, CHANGE action_nom_en action_nom_en VARCHAR(50) NOT NULL, CHANGE action_categorie_fr action_categorie_fr VARCHAR(50) NOT NULL, CHANGE action_categorie_en action_categorie_en VARCHAR(50) NOT NULL
        SQL);
    }
}
