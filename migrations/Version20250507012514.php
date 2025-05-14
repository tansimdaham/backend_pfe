<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250507012514 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Check if the foreign key exists before trying to drop it
        $foreignKeys = $this->connection->fetchAllAssociative("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'apprenant'
            AND CONSTRAINT_NAME = 'FK_C4EB462EFA55BACF'
        ");

        if (!empty($foreignKeys)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE apprenant DROP FOREIGN KEY FK_C4EB462EFA55BACF
            SQL);
        }

        // Check if the index exists before trying to drop it
        $indexes = $this->connection->fetchAllAssociative("
            SHOW INDEX FROM apprenant WHERE Key_name = 'UNIQ_C4EB462EFA55BACF'
        ");

        if (!empty($indexes)) {
            $this->addSql(<<<'SQL'
                DROP INDEX UNIQ_C4EB462EFA55BACF ON apprenant
            SQL);
        }

        // Check if the column exists before trying to drop it
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM apprenant');
        $columnNames = array_column($columns, 'Field');

        if (in_array('certificat_id', $columnNames)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE apprenant DROP certificat_id
            SQL);
        }

        // Check if the column already exists before adding it
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM certificat');
        $columnNames = array_column($columns, 'Field');

        if (!in_array('apprenant_id', $columnNames)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE certificat ADD apprenant_id INT NOT NULL
            SQL);

            $this->addSql(<<<'SQL'
                ALTER TABLE certificat ADD CONSTRAINT FK_27448F77C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
            SQL);

            $this->addSql(<<<'SQL'
                CREATE INDEX IDX_27448F77C5697D6D ON certificat (apprenant_id)
            SQL);
        }

        // Suppression des requêtes pour notification.user_id car la colonne existe déjà
        // $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
        // $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');
    }

    public function down(Schema $schema): void
    {
        // Check if the column already exists before adding it
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM apprenant');
        $columnNames = array_column($columns, 'Field');

        if (!in_array('certificat_id', $columnNames)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE apprenant ADD certificat_id INT DEFAULT NULL
            SQL);

            $this->addSql(<<<'SQL'
                ALTER TABLE apprenant ADD CONSTRAINT FK_C4EB462EFA55BACF FOREIGN KEY (certificat_id) REFERENCES certificat (id) ON UPDATE NO ACTION ON DELETE NO ACTION
            SQL);

            $this->addSql(<<<'SQL'
                CREATE UNIQUE INDEX UNIQ_C4EB462EFA55BACF ON apprenant (certificat_id)
            SQL);
        }

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
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM certificat');
        $columnNames = array_column($columns, 'Field');

        if (in_array('apprenant_id', $columnNames)) {
            $this->addSql(<<<'SQL'
                ALTER TABLE certificat DROP apprenant_id
            SQL);
        }

        // Suppression des requêtes pour notification.user_id car la colonne existe déjà
        // $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        // $this->addSql('DROP INDEX IDX_BF5476CAA76ED395 ON notification');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
