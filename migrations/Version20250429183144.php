<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250429183144 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si l'index unique sur name existe déjà
        $table = $schema->getTable('utilisateur');
        $hasIndex = false;
        foreach ($table->getIndexes() as $index) {
            if ($index->isUnique() && count($index->getColumns()) === 1 && $index->getColumns()[0] === 'name') {
                $hasIndex = true;
                break;
            }
        }

        if (!$hasIndex) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_1D1C63B35E237E06 ON utilisateur (name)');
        }
    }

    public function down(Schema $schema): void
    {
        // Vérifier si l'index unique sur name existe
        $table = $schema->getTable('utilisateur');
        $hasIndex = false;
        foreach ($table->getIndexes() as $index) {
            if ($index->isUnique() && count($index->getColumns()) === 1 && $index->getColumns()[0] === 'name') {
                $hasIndex = true;
                break;
            }
        }

        if ($hasIndex) {
            $this->addSql('DROP INDEX UNIQ_1D1C63B35E237E06 ON utilisateur');
        }
    }
}
