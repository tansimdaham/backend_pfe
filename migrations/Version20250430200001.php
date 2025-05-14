<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration personnalisée pour restructurer les entités Quiz, Competence, SousCompetence et Action
 */
final class Version20250430200001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restructuration des entités Quiz, Competence, SousCompetence et Action';
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
                    quiz_id INT DEFAULT NULL,
                    competence_id INT DEFAULT NULL,
                    nom_fr VARCHAR(250) NOT NULL,
                    nom_en VARCHAR(250) NOT NULL,
                    categorie_fr VARCHAR(250) DEFAULT NULL,
                    categorie_en VARCHAR(250) DEFAULT NULL,
                    INDEX IDX_47CC8C92853CD175 (quiz_id),
                    INDEX IDX_47CC8C9215761DAB (competence_id),
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // Vérifier si les contraintes existent déjà
        try {
            // Ajout des contraintes de clé étrangère pour competence
            $this->addSql(<<<'SQL'
                ALTER TABLE competence
                ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE
            SQL);
        } catch (\Exception $e) {
            // La contrainte existe déjà, on l'ignore
        }

        try {
            // Ajout des contraintes de clé étrangère pour sous_competence
            $this->addSql(<<<'SQL'
                ALTER TABLE sous_competence
                ADD CONSTRAINT FK_72AC3D9515761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE
            SQL);
        } catch (\Exception $e) {
            // La contrainte existe déjà, on l'ignore
        }

        try {
            // Ajout des contraintes de clé étrangère pour action (quiz)
            $this->addSql(<<<'SQL'
                ALTER TABLE action
                ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE
            SQL);
        } catch (\Exception $e) {
            // La contrainte existe déjà, on l'ignore
        }

        try {
            // Ajout des contraintes de clé étrangère pour action (competence)
            $this->addSql(<<<'SQL'
                ALTER TABLE action
                ADD CONSTRAINT FK_47CC8C9215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE
            SQL);
        } catch (\Exception $e) {
            // La contrainte existe déjà, on l'ignore
        }

        // Suppression des colonnes de l'entité Quiz qui ne sont plus nécessaires
        // MySQL ne supporte pas DROP COLUMN IF EXISTS, donc nous devons vérifier si les colonnes existent
        $columns = $this->connection->getSchemaManager()->listTableColumns('quiz');
        $columnNames = array_map('strtolower', array_keys($columns));

        if (in_array('competence_id', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN competence_id');
        }
        if (in_array('competence_nom_fr', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN competence_nom_fr');
        }
        if (in_array('competence_nom_en', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN competence_nom_en');
        }
        if (in_array('comp_categorie_fr', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN comp_categorie_fr');
        }
        if (in_array('comp_categorie_en', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN comp_categorie_en');
        }
        if (in_array('sous_competence_nom_fr', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN sous_competence_nom_fr');
        }
        if (in_array('sous_competence_nom_en', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN sous_competence_nom_en');
        }
        if (in_array('action_nom_fr', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN action_nom_fr');
        }
        if (in_array('action_nom_en', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN action_nom_en');
        }
        if (in_array('action_categorie_fr', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN action_categorie_fr');
        }
        if (in_array('action_categorie_en', $columnNames)) {
            $this->addSql('ALTER TABLE quiz DROP COLUMN action_categorie_en');
        }
    }

    public function down(Schema $schema): void
    {
        // Restauration des colonnes dans l'entité Quiz
        $this->addSql('ALTER TABLE quiz ADD COLUMN competence_id INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE quiz ADD COLUMN competence_nom_fr VARCHAR(250) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE quiz ADD COLUMN competence_nom_en VARCHAR(250) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE quiz ADD COLUMN comp_categorie_fr VARCHAR(250) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE quiz ADD COLUMN comp_categorie_en VARCHAR(250) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE quiz ADD COLUMN sous_competence_nom_fr VARCHAR(250) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE quiz ADD COLUMN sous_competence_nom_en VARCHAR(255) NOT NULL DEFAULT ""');
        $this->addSql('ALTER TABLE quiz ADD COLUMN action_nom_fr VARCHAR(250) DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD COLUMN action_nom_en VARCHAR(250) DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD COLUMN action_categorie_fr VARCHAR(250) DEFAULT NULL');
        $this->addSql('ALTER TABLE quiz ADD COLUMN action_categorie_en VARCHAR(250) DEFAULT NULL');

        // Suppression des contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9215761DAB
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

    public function isTransactional(): bool
    {
        return false;
    }
}
