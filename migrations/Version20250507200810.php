<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250507200810 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if the column exists in certificat table
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM certificat');
        $columnNames = array_column($columns, 'Field');

        if (in_array('apprenant_id', $columnNames)) {
            // Check if the foreign key already exists
            $foreignKeys = $this->connection->fetchAllAssociative("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'certificat'
                AND CONSTRAINT_NAME = 'FK_27448F77C5697D6D'
            ");

            if (empty($foreignKeys)) {
                // First, make sure all certificat records have valid apprenant_id values
                // Get the first apprenant ID
                $apprenantId = $this->connection->fetchOne("SELECT id FROM apprenant LIMIT 1");

                if ($apprenantId) {
                    $this->addSql("UPDATE certificat SET apprenant_id = $apprenantId WHERE apprenant_id = 0 OR apprenant_id IS NULL");
                }

                // Then add the foreign key constraint
                $this->addSql(<<<'SQL'
                    ALTER TABLE certificat ADD CONSTRAINT FK_27448F77C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
                SQL);
            }

            // Check if the index already exists
            $indexes = $this->connection->fetchAllAssociative("
                SHOW INDEX FROM certificat WHERE Key_name = 'IDX_27448F77C5697D6D'
            ");

            if (empty($indexes)) {
                $this->addSql(<<<'SQL'
                    CREATE INDEX IDX_27448F77C5697D6D ON certificat (apprenant_id)
                SQL);
            }
        }

        // Check if the sent_by_formateur column already exists in messagerie table
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM messagerie');
        $columnNames = array_column($columns, 'Field');

        if (!in_array('sent_by_formateur', $columnNames)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE messagerie ADD sent_by_formateur TINYINT(1) NOT NULL DEFAULT 0
            SQL);
        }

        // Check if the user_id column exists in notification table
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM notification');
        $columnNames = array_column($columns, 'Field');

        if (in_array('user_id', $columnNames)) {
            // Check if the foreign key already exists
            $foreignKeys = $this->connection->fetchAllAssociative("
                SELECT CONSTRAINT_NAME
                FROM information_schema.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = 'notification'
                AND CONSTRAINT_NAME = 'FK_BF5476CAA76ED395'
            ");

            if (empty($foreignKeys)) {
                $this->addSql(<<<'SQL'
                    ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE SET NULL
                SQL);
            }

            // Check if the index already exists
            $indexes = $this->connection->fetchAllAssociative("
                SHOW INDEX FROM notification WHERE Key_name = 'IDX_BF5476CAA76ED395'
            ");

            if (empty($indexes)) {
                $this->addSql(<<<'SQL'
                    CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)
                SQL);
            }
        }
    }

    public function down(Schema $schema): void
    {
        // Check if the foreign key exists before trying to drop it
        $foreignKeys = $this->connection->fetchAllAssociative("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'certificat'
            AND CONSTRAINT_NAME = 'FK_27448F77C5697D6D'
        ");

        if (!empty($foreignKeys)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE certificat DROP FOREIGN KEY FK_27448F77C5697D6D
            SQL);
        }

        // Check if the index exists before trying to drop it
        $indexes = $this->connection->fetchAllAssociative("
            SHOW INDEX FROM certificat WHERE Key_name = 'IDX_27448F77C5697D6D'
        ");

        if (!empty($indexes)) {
            $this->addSql(<<<'SQL'
                DROP INDEX IDX_27448F77C5697D6D ON certificat
            SQL);
        }

        // Check if the column exists before trying to drop it
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM messagerie');
        $columnNames = array_column($columns, 'Field');

        if (in_array('sent_by_formateur', $columnNames)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE messagerie DROP sent_by_formateur
            SQL);
        }

        // Check if the foreign key exists before trying to drop it
        $foreignKeys = $this->connection->fetchAllAssociative("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'notification'
            AND CONSTRAINT_NAME = 'FK_BF5476CAA76ED395'
        ");

        if (!empty($foreignKeys)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
            SQL);
        }

        // Check if the index exists before trying to drop it
        $indexes = $this->connection->fetchAllAssociative("
            SHOW INDEX FROM notification WHERE Key_name = 'IDX_BF5476CAA76ED395'
        ");

        if (!empty($indexes)) {
            $this->addSql(<<<'SQL'
                DROP INDEX IDX_BF5476CAA76ED395 ON notification
            SQL);
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
