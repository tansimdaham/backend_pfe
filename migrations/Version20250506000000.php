<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250506000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename read column to is_read in notification table';
    }

    public function up(Schema $schema): void
    {
        // Check if the 'read' column exists before trying to rename it
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM notification');
        $columnNames = array_column($columns, 'Field');

        if (in_array('read', $columnNames) && !in_array('is_read', $columnNames)) {
            // Rename the column from 'read' to 'is_read'
            $this->addSql('ALTER TABLE notification CHANGE `read` is_read TINYINT(1) DEFAULT NULL');
        } elseif (!in_array('is_read', $columnNames)) {
            // If 'read' doesn't exist but 'is_read' also doesn't exist, create it
            $this->addSql('ALTER TABLE notification ADD is_read TINYINT(1) DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        // Check if the 'is_read' column exists before trying to rename it
        $columns = $this->connection->fetchAllAssociative('SHOW COLUMNS FROM notification');
        $columnNames = array_column($columns, 'Field');

        if (in_array('is_read', $columnNames) && !in_array('read', $columnNames)) {
            // Rename the column back from 'is_read' to 'read'
            $this->addSql('ALTER TABLE notification CHANGE is_read `read` TINYINT(1) DEFAULT NULL');
        }
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
