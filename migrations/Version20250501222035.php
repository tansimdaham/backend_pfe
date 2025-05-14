<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501222035 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Spécifier explicitement le type de la colonne IDModule dans la table quiz';
    }

    public function up(Schema $schema): void
    {
        // Vérifier et créer les index si nécessaire
        $this->addSql("
            SET @index_exists_action = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'action'
                AND INDEX_NAME = 'IDX_47CC8C92CCB495C'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @create_idx_action = IF(@index_exists_action = 0,
                'CREATE INDEX IDX_47CC8C92CCB495C ON action (idmodule)',
                'SELECT 1');
            PREPARE stmt FROM @create_idx_action;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        $this->addSql("
            SET @index_exists_competence = (
                SELECT COUNT(*) FROM information_schema.STATISTICS
                WHERE TABLE_NAME = 'competence'
                AND INDEX_NAME = 'IDX_94D4687FCCB495C'
                AND TABLE_SCHEMA = DATABASE()
            );
            SET @create_idx_competence = IF(@index_exists_competence = 0,
                'CREATE INDEX IDX_94D4687FCCB495C ON competence (idmodule)',
                'SELECT 1');
            PREPARE stmt FROM @create_idx_competence;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        ");

        // Spécifier explicitement le type de la colonne IDModule
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz MODIFY IDModule VARCHAR(50) NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Rien à faire ici, car nous ne voulons pas revenir à un état où le type n'est pas spécifié
        // Les index sont déjà recréés dans la méthode up()
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
