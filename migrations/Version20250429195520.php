<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429195520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les colonnes existent déjà
        $table = $schema->getTable('utilisateur');
        $columnsToAdd = [];

        if (!$table->hasColumn('approved_by_id')) {
            $columnsToAdd[] = 'ADD approved_by_id INT DEFAULT NULL';
        }

        if (!$table->hasColumn('is_approved')) {
            $columnsToAdd[] = 'ADD is_approved TINYINT(1) DEFAULT 0 NOT NULL';
        }

        if (!$table->hasColumn('approved_at')) {
            $columnsToAdd[] = 'ADD approved_at DATETIME DEFAULT NULL';
        }

        if (!$table->hasColumn('rejection_reason')) {
            $columnsToAdd[] = 'ADD rejection_reason VARCHAR(255) DEFAULT NULL';
        }

        // Ajouter les colonnes qui n'existent pas encore
        if (!empty($columnsToAdd)) {
            $this->addSql('ALTER TABLE utilisateur ' . implode(', ', $columnsToAdd));
        }

        // Vérifier si la contrainte de clé étrangère existe déjà
        $foreignKeys = $table->getForeignKeys();
        $hasForeignKey = false;
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getLocalColumns() === ['approved_by_id']) {
                $hasForeignKey = true;
                break;
            }
        }

        if (!$hasForeignKey && $table->hasColumn('approved_by_id')) {
            $this->addSql('ALTER TABLE utilisateur ADD CONSTRAINT FK_1D1C63B32D234F6A FOREIGN KEY (approved_by_id) REFERENCES administrateur (id)');
        }

        // Vérifier si l'index existe déjà
        $indexes = $table->getIndexes();
        $hasIndex = false;
        foreach ($indexes as $index) {
            if ($index->getColumns() === ['approved_by_id']) {
                $hasIndex = true;
                break;
            }
        }

        if (!$hasIndex && $table->hasColumn('approved_by_id')) {
            $this->addSql('CREATE INDEX IDX_1D1C63B32D234F6A ON utilisateur (approved_by_id)');
        }
    }

    public function down(Schema $schema): void
    {
        // Vérifier si les colonnes existent
        $table = $schema->getTable('utilisateur');
        $columnsToDrop = [];

        // Vérifier si la contrainte de clé étrangère existe
        $foreignKeys = $table->getForeignKeys();
        $hasForeignKey = false;
        foreach ($foreignKeys as $foreignKey) {
            if ($foreignKey->getLocalColumns() === ['approved_by_id']) {
                $hasForeignKey = true;
                break;
            }
        }

        if ($hasForeignKey) {
            $this->addSql('ALTER TABLE utilisateur DROP FOREIGN KEY FK_1D1C63B32D234F6A');
        }

        // Vérifier si l'index existe
        $indexes = $table->getIndexes();
        $hasIndex = false;
        foreach ($indexes as $index) {
            if ($index->getColumns() === ['approved_by_id']) {
                $hasIndex = true;
                break;
            }
        }

        if ($hasIndex) {
            $this->addSql('DROP INDEX IDX_1D1C63B32D234F6A ON utilisateur');
        }

        // Vérifier quelles colonnes existent
        if ($table->hasColumn('approved_by_id')) {
            $columnsToDrop[] = 'DROP approved_by_id';
        }

        if ($table->hasColumn('is_approved')) {
            $columnsToDrop[] = 'DROP is_approved';
        }

        if ($table->hasColumn('approved_at')) {
            $columnsToDrop[] = 'DROP approved_at';
        }

        if ($table->hasColumn('rejection_reason')) {
            $columnsToDrop[] = 'DROP rejection_reason';
        }

        // Supprimer les colonnes qui existent
        if (!empty($columnsToDrop)) {
            $this->addSql('ALTER TABLE utilisateur ' . implode(', ', $columnsToDrop));
        }
    }
}
