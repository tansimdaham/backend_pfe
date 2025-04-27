<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427143202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE nom_fr nom_fr VARCHAR(250) NOT NULL, CHANGE nom_en nom_en VARCHAR(250) NOT NULL, CHANGE point_fort_fr point_fort_fr VARCHAR(250) DEFAULT NULL, CHANGE point_fort_en point_fort_en VARCHAR(250) DEFAULT NULL, CHANGE competence_nom_fr competence_nom_fr VARCHAR(250) NOT NULL, CHANGE competence_nom_en competence_nom_en VARCHAR(250) NOT NULL, CHANGE comp_categorie_fr comp_categorie_fr VARCHAR(250) NOT NULL, CHANGE comp_categorie_en comp_categorie_en VARCHAR(250) NOT NULL, CHANGE sous_competence_nom_fr sous_competence_nom_fr VARCHAR(250) NOT NULL, CHANGE action_nom_fr action_nom_fr VARCHAR(250) DEFAULT NULL, CHANGE action_nom_en action_nom_en VARCHAR(250) DEFAULT NULL, CHANGE action_categorie_fr action_categorie_fr VARCHAR(250) DEFAULT NULL, CHANGE action_categorie_en action_categorie_en VARCHAR(250) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE nom_fr nom_fr VARCHAR(50) NOT NULL, CHANGE nom_en nom_en VARCHAR(50) NOT NULL, CHANGE point_fort_fr point_fort_fr VARCHAR(50) DEFAULT NULL, CHANGE point_fort_en point_fort_en VARCHAR(50) DEFAULT NULL, CHANGE comp_categorie_fr comp_categorie_fr VARCHAR(50) NOT NULL, CHANGE comp_categorie_en comp_categorie_en VARCHAR(50) NOT NULL, CHANGE competence_nom_fr competence_nom_fr VARCHAR(50) NOT NULL, CHANGE competence_nom_en competence_nom_en VARCHAR(50) NOT NULL, CHANGE sous_competence_nom_fr sous_competence_nom_fr VARCHAR(50) NOT NULL, CHANGE action_nom_fr action_nom_fr VARCHAR(50) DEFAULT NULL, CHANGE action_nom_en action_nom_en VARCHAR(50) DEFAULT NULL, CHANGE action_categorie_fr action_categorie_fr VARCHAR(50) DEFAULT NULL, CHANGE action_categorie_en action_categorie_en VARCHAR(50) DEFAULT NULL
        SQL);
    }
}
