<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250423142244 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE cours DROP description
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575853CD175
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1323A575853CD175 ON evaluation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP quiz_id
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_quiz_idmodule ON quiz
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD evaluation_id INT NOT NULL, ADD comp_categorie_fr VARCHAR(50) NOT NULL, ADD comp_categorie_en VARCHAR(50) NOT NULL, ADD sous_competence_nom_fr VARCHAR(50) NOT NULL, ADD sous_competence_nom_en VARCHAR(255) NOT NULL, ADD action_nom_fr VARCHAR(50) NOT NULL, ADD action_nom_en VARCHAR(50) NOT NULL, ADD action_categorie_fr VARCHAR(50) NOT NULL, ADD action_categorie_en VARCHAR(50) NOT NULL, DROP competences, DROP actions, DROP SousCompetence_Nom_FR, DROP SousCompetence_Nom_EN, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE category category VARCHAR(50) NOT NULL, CHANGE type type VARCHAR(50) NOT NULL, CHANGE main_surface main_surface VARCHAR(50) NOT NULL, CHANGE vitesse vitesse VARCHAR(50) NOT NULL, CHANGE surface surface INT NOT NULL, CHANGE main main INT NOT NULL, CHANGE nom_fr nom_fr VARCHAR(50) NOT NULL, CHANGE nom_en nom_en VARCHAR(50) NOT NULL, CHANGE point_fort_fr point_fort_fr VARCHAR(50) NOT NULL, CHANGE point_fort_en point_fort_en VARCHAR(50) NOT NULL, CHANGE Competence_ID competence_id INT NOT NULL, CHANGE Competence_Nom_FR competence_nom_fr VARCHAR(50) NOT NULL, CHANGE Competence_Nom_EN competence_nom_en VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A412FA92456C5646 ON quiz (evaluation_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD quiz_id VARCHAR(50) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (idmodule) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1323A575853CD175 ON evaluation (quiz_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE cours ADD description LONGTEXT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92456C5646
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A412FA92456C5646 ON quiz
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD competences JSON DEFAULT NULL, ADD actions JSON DEFAULT NULL, ADD SousCompetence_Nom_FR VARCHAR(255) DEFAULT NULL, ADD SousCompetence_Nom_EN VARCHAR(255) DEFAULT NULL, DROP evaluation_id, DROP comp_categorie_fr, DROP comp_categorie_en, DROP sous_competence_nom_fr, DROP sous_competence_nom_en, DROP action_nom_fr, DROP action_nom_en, DROP action_categorie_fr, DROP action_categorie_en, CHANGE id id INT NOT NULL, CHANGE category category VARCHAR(50) DEFAULT NULL, CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE main_surface main_surface VARCHAR(50) DEFAULT NULL, CHANGE vitesse vitesse VARCHAR(50) DEFAULT NULL, CHANGE surface surface INT DEFAULT NULL, CHANGE main main INT DEFAULT NULL, CHANGE nom_fr nom_fr VARCHAR(255) DEFAULT NULL, CHANGE nom_en nom_en VARCHAR(255) DEFAULT NULL, CHANGE point_fort_fr point_fort_fr VARCHAR(255) DEFAULT NULL, CHANGE point_fort_en point_fort_en VARCHAR(255) DEFAULT NULL, CHANGE competence_id Competence_ID VARCHAR(50) DEFAULT NULL, CHANGE competence_nom_fr Competence_Nom_FR VARCHAR(255) DEFAULT NULL, CHANGE competence_nom_en Competence_Nom_EN VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_quiz_idmodule ON quiz (idmodule)
        SQL);
    }
}
