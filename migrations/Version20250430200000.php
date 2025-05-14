<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration manuelle pour ajouter les tables de compétences, sous-compétences et actions
 * sans supprimer les champs existants dans la table quiz
 */
final class Version20250430200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les tables de compétences, sous-compétences et actions sans supprimer les champs existants';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les tables existent déjà
        $tables = $this->connection->getSchemaManager()->listTableNames();
        
        // Création de la table competence si elle n'existe pas
        if (!in_array('competence', $tables)) {
            $this->addSql(<<<'SQL'
                CREATE TABLE competence (
                    id INT AUTO_INCREMENT NOT NULL,
                    quiz_id INT NOT NULL,
                    nom_fr VARCHAR(250) NOT NULL,
                    nom_en VARCHAR(250) NOT NULL,
                    categorie_fr VARCHAR(250) DEFAULT NULL,
                    categorie_en VARCHAR(250) DEFAULT NULL,
                    INDEX IDX_94D4687F853CD175 (quiz_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // Création de la table sous_competence si elle n'existe pas
        if (!in_array('sous_competence', $tables)) {
            $this->addSql(<<<'SQL'
                CREATE TABLE sous_competence (
                    id INT AUTO_INCREMENT NOT NULL,
                    competence_id INT NOT NULL,
                    nom_fr VARCHAR(250) NOT NULL,
                    nom_en VARCHAR(250) NOT NULL,
                    INDEX IDX_72AC3D9515761DAB (competence_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // Création de la table action si elle n'existe pas
        if (!in_array('action', $tables)) {
            $this->addSql(<<<'SQL'
                CREATE TABLE action (
                    id INT AUTO_INCREMENT NOT NULL,
                    competence_id INT DEFAULT NULL,
                    quiz_id INT DEFAULT NULL,
                    nom_fr VARCHAR(250) NOT NULL,
                    nom_en VARCHAR(250) NOT NULL,
                    categorie_fr VARCHAR(250) DEFAULT NULL,
                    categorie_en VARCHAR(250) DEFAULT NULL,
                    INDEX IDX_47CC8C9215761DAB (competence_id),
                    INDEX IDX_47CC8C92853CD175 (quiz_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // Ajout des contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE competence
            ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE sous_competence
            ADD CONSTRAINT FK_72AC3D9515761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE action
            ADD CONSTRAINT FK_47CC8C9215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE action
            ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Suppression des contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9215761DAB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE sous_competence DROP FOREIGN KEY FK_72AC3D9515761DAB
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F853CD175
        SQL);

        // Suppression des tables
        $this->addSql(<<<'SQL'
            DROP TABLE IF EXISTS action
        SQL);

        $this->addSql(<<<'SQL'
            DROP TABLE IF EXISTS sous_competence
        SQL);

        $this->addSql(<<<'SQL'
            DROP TABLE IF EXISTS competence
        SQL);
    }
}
