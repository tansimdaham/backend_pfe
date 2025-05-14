<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration manuelle pour modifier les colonnes quiz_id en NOT NULL
 */
final class Version20250501235000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modifie les colonnes quiz_id en NOT NULL';
    }

    public function up(Schema $schema): void
    {
        // Supprimer les contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F853CD175
        SQL);

        // Modifier les colonnes pour les rendre NOT NULL
        $this->addSql(<<<'SQL'
            UPDATE action SET quiz_id = (SELECT id FROM quiz LIMIT 1) WHERE quiz_id IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            UPDATE competence SET quiz_id = (SELECT id FROM quiz LIMIT 1) WHERE quiz_id IS NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action MODIFY quiz_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence MODIFY quiz_id INT NOT NULL
        SQL);

        // Recréer les contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Supprimer les contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F853CD175
        SQL);

        // Modifier les colonnes pour les rendre NULL
        $this->addSql(<<<'SQL'
            ALTER TABLE action MODIFY quiz_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence MODIFY quiz_id INT DEFAULT NULL
        SQL);

        // Recréer les contraintes de clé étrangère
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
