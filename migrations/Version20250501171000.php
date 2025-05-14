<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250501171000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update competence and action tables to make idmodule NOT NULL';
    }

    public function up(Schema $schema): void
    {
        // Make idmodule NOT NULL after data migration
        $this->addSql('UPDATE competence SET idmodule = CONCAT("default_", id) WHERE idmodule IS NULL');
        $this->addSql('UPDATE action SET idmodule = CONCAT("default_", id) WHERE idmodule IS NULL');
        
        $this->addSql('ALTER TABLE competence MODIFY idmodule VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE action MODIFY idmodule VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Make idmodule nullable again
        $this->addSql('ALTER TABLE competence MODIFY idmodule VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE action MODIFY idmodule VARCHAR(50) DEFAULT NULL');
    }
}
