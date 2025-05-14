<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250430162642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les tables existent déjà
        $tables = $schema->getTables();
        $tableNames = array_map(function($table) {
            return $table->getName();
        }, $tables);

        // Créer les tables si elles n'existent pas
        if (!in_array('action', $tableNames)) {
            $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, competence_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, categorie_fr VARCHAR(250) DEFAULT NULL, categorie_en VARCHAR(250) DEFAULT NULL, INDEX IDX_47CC8C9215761DAB (competence_id), INDEX IDX_47CC8C92853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('competence', $tableNames)) {
            $this->addSql('CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, categorie_fr VARCHAR(250) DEFAULT NULL, categorie_en VARCHAR(250) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('quiz_competence', $tableNames)) {
            $this->addSql('CREATE TABLE quiz_competence (quiz_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_DDBB4F43853CD175 (quiz_id), INDEX IDX_DDBB4F4315761DAB (competence_id), PRIMARY KEY(quiz_id, competence_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (!in_array('sous_competence', $tableNames)) {
            $this->addSql('CREATE TABLE sous_competence (id INT AUTO_INCREMENT NOT NULL, competence_id INT NOT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, INDEX IDX_72AC3D9515761DAB (competence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        // Ajouter les contraintes de clé étrangère si les tables existent
        if (in_array('action', $tableNames) && in_array('competence', $tableNames)) {
            // Vérifier si la contrainte existe déjà
            $actionTable = $schema->getTable('action');
            $hasForeignKey1 = false;
            foreach ($actionTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['competence_id'] && $foreignKey->getForeignTableName() === 'competence') {
                    $hasForeignKey1 = true;
                    break;
                }
            }

            if (!$hasForeignKey1) {
                $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C9215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
            }
        }

        if (in_array('action', $tableNames) && in_array('quiz', $tableNames)) {
            // Vérifier si la contrainte existe déjà
            $actionTable = $schema->getTable('action');
            $hasForeignKey2 = false;
            foreach ($actionTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['quiz_id'] && $foreignKey->getForeignTableName() === 'quiz') {
                    $hasForeignKey2 = true;
                    break;
                }
            }

            if (!$hasForeignKey2) {
                $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
            }
        }

        if (in_array('quiz_competence', $tableNames) && in_array('quiz', $tableNames)) {
            // Vérifier si la contrainte existe déjà
            $quizCompetenceTable = $schema->getTable('quiz_competence');
            $hasForeignKey3 = false;
            foreach ($quizCompetenceTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['quiz_id'] && $foreignKey->getForeignTableName() === 'quiz') {
                    $hasForeignKey3 = true;
                    break;
                }
            }

            if (!$hasForeignKey3) {
                $this->addSql('ALTER TABLE quiz_competence ADD CONSTRAINT FK_DDBB4F43853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE');
            }
        }

        if (in_array('quiz_competence', $tableNames) && in_array('competence', $tableNames)) {
            // Vérifier si la contrainte existe déjà
            $quizCompetenceTable = $schema->getTable('quiz_competence');
            $hasForeignKey4 = false;
            foreach ($quizCompetenceTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['competence_id'] && $foreignKey->getForeignTableName() === 'competence') {
                    $hasForeignKey4 = true;
                    break;
                }
            }

            if (!$hasForeignKey4) {
                $this->addSql('ALTER TABLE quiz_competence ADD CONSTRAINT FK_DDBB4F4315761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE');
            }
        }

        if (in_array('sous_competence', $tableNames) && in_array('competence', $tableNames)) {
            // Vérifier si la contrainte existe déjà
            $sousCompetenceTable = $schema->getTable('sous_competence');
            $hasForeignKey5 = false;
            foreach ($sousCompetenceTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['competence_id'] && $foreignKey->getForeignTableName() === 'competence') {
                    $hasForeignKey5 = true;
                    break;
                }
            }

            if (!$hasForeignKey5) {
                $this->addSql('ALTER TABLE sous_competence ADD CONSTRAINT FK_72AC3D9515761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Vérifier si les tables existent
        $tables = $schema->getTables();
        $tableNames = array_map(function($table) {
            return $table->getName();
        }, $tables);

        // Supprimer les contraintes de clé étrangère si les tables existent
        if (in_array('action', $tableNames)) {
            $actionTable = $schema->getTable('action');
            foreach ($actionTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['competence_id'] && $foreignKey->getForeignTableName() === 'competence') {
                    $this->addSql('ALTER TABLE action DROP FOREIGN KEY ' . $foreignKey->getName());
                }
                if ($foreignKey->getLocalColumns() === ['quiz_id'] && $foreignKey->getForeignTableName() === 'quiz') {
                    $this->addSql('ALTER TABLE action DROP FOREIGN KEY ' . $foreignKey->getName());
                }
            }
        }

        if (in_array('quiz_competence', $tableNames)) {
            $quizCompetenceTable = $schema->getTable('quiz_competence');
            foreach ($quizCompetenceTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['quiz_id'] && $foreignKey->getForeignTableName() === 'quiz') {
                    $this->addSql('ALTER TABLE quiz_competence DROP FOREIGN KEY ' . $foreignKey->getName());
                }
                if ($foreignKey->getLocalColumns() === ['competence_id'] && $foreignKey->getForeignTableName() === 'competence') {
                    $this->addSql('ALTER TABLE quiz_competence DROP FOREIGN KEY ' . $foreignKey->getName());
                }
            }
        }

        if (in_array('sous_competence', $tableNames)) {
            $sousCompetenceTable = $schema->getTable('sous_competence');
            foreach ($sousCompetenceTable->getForeignKeys() as $foreignKey) {
                if ($foreignKey->getLocalColumns() === ['competence_id'] && $foreignKey->getForeignTableName() === 'competence') {
                    $this->addSql('ALTER TABLE sous_competence DROP FOREIGN KEY ' . $foreignKey->getName());
                }
            }
        }

        // Supprimer les tables si elles existent
        if (in_array('action', $tableNames)) {
            $this->addSql('DROP TABLE action');
        }

        if (in_array('competence', $tableNames)) {
            $this->addSql('DROP TABLE competence');
        }

        if (in_array('quiz_competence', $tableNames)) {
            $this->addSql('DROP TABLE quiz_competence');
        }

        if (in_array('sous_competence', $tableNames)) {
            $this->addSql('DROP TABLE sous_competence');
        }
    }
}
