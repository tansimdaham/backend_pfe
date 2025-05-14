<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250512000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at field to evaluation table';
    }

    public function up(Schema $schema): void
    {
        // Add created_at column with default value of current timestamp
        $this->addSql('ALTER TABLE evaluation ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        
        // Update existing records to have a created_at value
        $this->addSql('UPDATE evaluation SET created_at = NOW() WHERE created_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove created_at column
        $this->addSql('ALTER TABLE evaluation DROP created_at');
    }
}
