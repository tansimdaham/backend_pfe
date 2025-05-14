<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250501170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add idmodule column to competence and action tables';
    }

    public function up(Schema $schema): void
    {
        // Add idmodule column to competence table
        $this->addSql('ALTER TABLE competence ADD idmodule VARCHAR(50) DEFAULT NULL');
        
        // Add idmodule column to action table
        $this->addSql('ALTER TABLE action ADD idmodule VARCHAR(50) DEFAULT NULL');
        
        // Update idmodule values in competence table based on quiz relationship
        $this->addSql('UPDATE competence c JOIN quiz q ON c.quiz_id = q.id SET c.idmodule = q.idmodule');
        
        // Update idmodule values in action table based on quiz relationship
        $this->addSql('UPDATE action a JOIN quiz q ON a.quiz_id = q.id SET a.idmodule = q.idmodule');
        
        // Add indexes for better performance
        $this->addSql('CREATE INDEX IDX_COMPETENCE_IDMODULE ON competence (idmodule)');
        $this->addSql('CREATE INDEX IDX_ACTION_IDMODULE ON action (idmodule)');
    }

    public function down(Schema $schema): void
    {
        // Remove indexes
        $this->addSql('DROP INDEX IDX_COMPETENCE_IDMODULE ON competence');
        $this->addSql('DROP INDEX IDX_ACTION_IDMODULE ON action');
        
        // Remove idmodule columns
        $this->addSql('ALTER TABLE competence DROP COLUMN idmodule');
        $this->addSql('ALTER TABLE action DROP COLUMN idmodule');
    }
}
