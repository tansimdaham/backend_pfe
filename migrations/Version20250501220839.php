<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501220839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Correction des relations entre Quiz, Competence et Action';
    }

    public function up(Schema $schema): void
    {
        // Vérifier et supprimer les anciennes contraintes de clé étrangère
        $this->addSql("
            SET @constraint_name_competence = (
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'competence'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'quiz_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
                LIMIT 1
            );
            SET @drop_fk_competence = IF(@constraint_name_competence IS NOT NULL,
                CONCAT('ALTER TABLE competence DROP FOREIGN KEY ', @constraint_name_competence),
                'SELECT 1');
            PREPARE stmt FROM @drop_fk_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @constraint_name_action = (
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'action'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'quiz_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
                LIMIT 1
            );
            SET @drop_fk_action = IF(@constraint_name_action IS NOT NULL,
                CONCAT('ALTER TABLE action DROP FOREIGN KEY ', @constraint_name_action),
                'SELECT 1');
            PREPARE stmt FROM @drop_fk_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier et supprimer les anciens index
        $this->addSql("
            SET @index_exists_competence = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'competence'
                AND INDEX_NAME = 'IDX_94D4687F853CD175'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @drop_idx_competence = IF(@index_exists_competence > 0,
                'DROP INDEX IDX_94D4687F853CD175 ON competence',
                'SELECT 1');
            PREPARE stmt FROM @drop_idx_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @index_exists_action = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'action'
                AND INDEX_NAME = 'IDX_47CC8C92853CD175'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @drop_idx_action = IF(@index_exists_action > 0,
                'DROP INDEX IDX_47CC8C92853CD175 ON action',
                'SELECT 1');
            PREPARE stmt FROM @drop_idx_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier et créer les nouveaux index pour idmodule
        $this->addSql("
            SET @index_exists_competence_idmodule = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'competence'
                AND INDEX_NAME = 'IDX_94D4687FCCB495C'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @create_idx_competence = IF(@index_exists_competence_idmodule = 0,
                'CREATE INDEX IDX_94D4687FCCB495C ON competence (idmodule)',
                'SELECT 1');
            PREPARE stmt FROM @create_idx_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @index_exists_action_idmodule = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'action'
                AND INDEX_NAME = 'IDX_47CC8C92CCB495C'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @create_idx_action = IF(@index_exists_action_idmodule = 0,
                'CREATE INDEX IDX_47CC8C92CCB495C ON action (idmodule)',
                'SELECT 1');
            PREPARE stmt FROM @create_idx_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier et ajouter les nouvelles contraintes de clé étrangère
        $this->addSql("
            SET @constraint_exists_competence = (
                SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'competence'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'idmodule'
                AND CONSTRAINT_SCHEMA = DATABASE()
            );
            SET @add_fk_competence = IF(@constraint_exists_competence = 0,
                'ALTER TABLE competence ADD CONSTRAINT FK_94D4687FCCB495C FOREIGN KEY (idmodule) REFERENCES quiz (IDModule)',
                'SELECT 1');
            PREPARE stmt FROM @add_fk_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @constraint_exists_action = (
                SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'action'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'idmodule'
                AND CONSTRAINT_SCHEMA = DATABASE()
            );
            SET @add_fk_action = IF(@constraint_exists_action = 0,
                'ALTER TABLE action ADD CONSTRAINT FK_47CC8C92CCB495C FOREIGN KEY (idmodule) REFERENCES quiz (IDModule)',
                'SELECT 1');
            PREPARE stmt FROM @add_fk_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier si les colonnes quiz_id existent
        $this->addSql("
            SET @column_exists_competence = (
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_NAME = 'competence'
                AND COLUMN_NAME = 'quiz_id'
                AND TABLE_SCHEMA = DATABASE()
            );

            SET @column_exists_action = (
                SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_NAME = 'action'
                AND COLUMN_NAME = 'quiz_id'
                AND TABLE_SCHEMA = DATABASE()
            );
        ");

        // Mise à jour des valeurs idmodule dans competence et action si les colonnes quiz_id existent
        $this->addSql("
            SET @update_competence = IF(@column_exists_competence > 0,
                'UPDATE competence c JOIN quiz q ON c.quiz_id = q.id SET c.idmodule = q.IDModule WHERE c.idmodule IS NULL OR c.idmodule = \"\"',
                'SELECT 1');
            PREPARE stmt FROM @update_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @update_action = IF(@column_exists_action > 0,
                'UPDATE action a JOIN quiz q ON a.quiz_id = q.id SET a.idmodule = q.IDModule WHERE a.idmodule IS NULL OR a.idmodule = \"\"',
                'SELECT 1');
            PREPARE stmt FROM @update_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function down(Schema $schema): void
    {
        // Vérifier et supprimer les nouvelles contraintes de clé étrangère
        $this->addSql("
            SET @constraint_name_competence = (
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'competence'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'idmodule'
                AND CONSTRAINT_SCHEMA = DATABASE()
                LIMIT 1
            );
            SET @drop_fk_competence = IF(@constraint_name_competence IS NOT NULL,
                CONCAT('ALTER TABLE competence DROP FOREIGN KEY ', @constraint_name_competence),
                'SELECT 1');
            PREPARE stmt FROM @drop_fk_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @constraint_name_action = (
                SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'action'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'idmodule'
                AND CONSTRAINT_SCHEMA = DATABASE()
                LIMIT 1
            );
            SET @drop_fk_action = IF(@constraint_name_action IS NOT NULL,
                CONCAT('ALTER TABLE action DROP FOREIGN KEY ', @constraint_name_action),
                'SELECT 1');
            PREPARE stmt FROM @drop_fk_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier et supprimer les nouveaux index
        $this->addSql("
            SET @index_exists_competence = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'competence'
                AND INDEX_NAME = 'IDX_94D4687FCCB495C'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @drop_idx_competence = IF(@index_exists_competence > 0,
                'DROP INDEX IDX_94D4687FCCB495C ON competence',
                'SELECT 1');
            PREPARE stmt FROM @drop_idx_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @index_exists_action = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'action'
                AND INDEX_NAME = 'IDX_47CC8C92CCB495C'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @drop_idx_action = IF(@index_exists_action > 0,
                'DROP INDEX IDX_47CC8C92CCB495C ON action',
                'SELECT 1');
            PREPARE stmt FROM @drop_idx_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier et créer les anciens index
        $this->addSql("
            SET @index_exists_competence_quiz_id = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'competence'
                AND INDEX_NAME = 'IDX_94D4687F853CD175'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @create_idx_competence = IF(@index_exists_competence_quiz_id = 0,
                'CREATE INDEX IDX_94D4687F853CD175 ON competence (quiz_id)',
                'SELECT 1');
            PREPARE stmt FROM @create_idx_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @index_exists_action_quiz_id = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'action'
                AND INDEX_NAME = 'IDX_47CC8C92853CD175'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @create_idx_action = IF(@index_exists_action_quiz_id = 0,
                'CREATE INDEX IDX_47CC8C92853CD175 ON action (quiz_id)',
                'SELECT 1');
            PREPARE stmt FROM @create_idx_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Vérifier et ajouter les anciennes contraintes de clé étrangère
        $this->addSql("
            SET @constraint_exists_competence = (
                SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'competence'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'quiz_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
            );
            SET @add_fk_competence = IF(@constraint_exists_competence = 0,
                'ALTER TABLE competence ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)',
                'SELECT 1');
            PREPARE stmt FROM @add_fk_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @constraint_exists_action = (
                SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_NAME = 'action'
                AND REFERENCED_TABLE_NAME = 'quiz'
                AND COLUMN_NAME = 'quiz_id'
                AND CONSTRAINT_SCHEMA = DATABASE()
            );
            SET @add_fk_action = IF(@constraint_exists_action = 0,
                'ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)',
                'SELECT 1');
            PREPARE stmt FROM @add_fk_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
