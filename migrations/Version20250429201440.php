<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429201440 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne is_approved existe déjà
        $table = $schema->getTable('utilisateur');
        if (!$table->hasColumn('is_approved')) {
            $this->addSql('ALTER TABLE utilisateur ADD is_approved TINYINT(1) DEFAULT 0 NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Vérifier si la colonne is_approved existe
        $table = $schema->getTable('utilisateur');
        if ($table->hasColumn('is_approved')) {
            $this->addSql('ALTER TABLE utilisateur DROP is_approved');
        }
    }
}
