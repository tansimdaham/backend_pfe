<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250428173801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la colonne role existe déjà
        $columns = $schema->getTable('utilisateur')->getColumns();
        $hasRole = false;
        foreach ($columns as $column) {
            if ($column->getName() === 'role') {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            $this->addSql('ALTER TABLE utilisateur ADD role VARCHAR(50) NOT NULL');
        }

        // Modifier profile_image pour qu'il puisse être NULL
        $this->addSql('ALTER TABLE utilisateur CHANGE profile_image profile_image VARCHAR(255) DEFAULT NULL');

        // L'index unique sur email existe déjà, donc on ne l'ajoute pas
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur (email)');
    }

    public function down(Schema $schema): void
    {
        // Vérifier si la colonne role existe
        $columns = $schema->getTable('utilisateur')->getColumns();
        $hasRole = false;
        foreach ($columns as $column) {
            if ($column->getName() === 'role') {
                $hasRole = true;
                break;
            }
        }

        if ($hasRole) {
            $this->addSql('ALTER TABLE utilisateur DROP role');
        }

        // Modifier profile_image pour qu'il ne puisse pas être NULL
        $this->addSql('ALTER TABLE utilisateur CHANGE profile_image profile_image VARCHAR(255) NOT NULL');

        // L'index unique sur email existe déjà, donc on ne le supprime pas
        // $this->addSql('DROP INDEX UNIQ_1D1C63B3E7927C74 ON utilisateur');
    }
}
