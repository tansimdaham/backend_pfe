<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423234241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE category category VARCHAR(50) NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE main_surface main_surface VARCHAR(50) NOT NULL, CHANGE vitesse vitesse VARCHAR(50) NOT NULL, CHANGE surface surface INT NOT NULL, CHANGE main main INT NOT NULL, CHANGE nom_fr nom_fr VARCHAR(50) NOT NULL, CHANGE nom_en nom_en VARCHAR(50) NOT NULL, CHANGE point_fort_fr point_fort_fr VARCHAR(50) NOT NULL, CHANGE point_fort_en point_fort_en VARCHAR(50) NOT NULL, CHANGE competence_id competence_id INT NOT NULL, CHANGE competence_nom_fr competence_nom_fr VARCHAR(50) NOT NULL, CHANGE competence_nom_en competence_nom_en VARCHAR(50) NOT NULL, CHANGE comp_categorie_fr comp_categorie_fr VARCHAR(50) NOT NULL, CHANGE comp_categorie_en comp_categorie_en VARCHAR(50) NOT NULL, CHANGE sous_competence_nom_fr sous_competence_nom_fr VARCHAR(50) NOT NULL, CHANGE sous_competence_nom_en sous_competence_nom_en VARCHAR(255) NOT NULL, CHANGE action_nom_fr action_nom_fr VARCHAR(50) NOT NULL, CHANGE action_nom_en action_nom_en VARCHAR(50) NOT NULL, CHANGE action_categorie_fr action_categorie_fr VARCHAR(50) NOT NULL, CHANGE action_categorie_en action_categorie_en VARCHAR(50) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz CHANGE category category VARCHAR(50) DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE main_surface main_surface VARCHAR(255) DEFAULT NULL, CHANGE vitesse vitesse VARCHAR(255) DEFAULT NULL, CHANGE surface surface TINYINT(1) DEFAULT NULL, CHANGE main main TINYINT(1) DEFAULT NULL, CHANGE nom_fr nom_fr VARCHAR(255) DEFAULT NULL, CHANGE nom_en nom_en VARCHAR(255) DEFAULT NULL, CHANGE point_fort_fr point_fort_fr VARCHAR(255) DEFAULT NULL, CHANGE point_fort_en point_fort_en VARCHAR(255) DEFAULT NULL, CHANGE competence_id competence_id INT DEFAULT NULL, CHANGE comp_categorie_fr comp_categorie_fr VARCHAR(255) DEFAULT NULL, CHANGE comp_categorie_en comp_categorie_en VARCHAR(255) DEFAULT NULL, CHANGE competence_nom_fr competence_nom_fr VARCHAR(255) DEFAULT NULL, CHANGE competence_nom_en competence_nom_en VARCHAR(255) DEFAULT NULL, CHANGE sous_competence_nom_fr sous_competence_nom_fr VARCHAR(255) DEFAULT NULL, CHANGE sous_competence_nom_en sous_competence_nom_en VARCHAR(255) DEFAULT NULL, CHANGE action_nom_fr action_nom_fr VARCHAR(255) DEFAULT NULL, CHANGE action_nom_en action_nom_en VARCHAR(255) DEFAULT NULL, CHANGE action_categorie_fr action_categorie_fr VARCHAR(255) DEFAULT NULL, CHANGE action_categorie_en action_categorie_en VARCHAR(255) DEFAULT NULL
        SQL);
    }
}
