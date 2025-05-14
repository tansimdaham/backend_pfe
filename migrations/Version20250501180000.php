<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250501180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove quiz_id column from competence and action tables';
    }

    public function up(Schema $schema): void
    {
        // Drop foreign key constraints first
        $this->addSql('ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F853CD175');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175');
        
        // Drop indexes
        $this->addSql('DROP INDEX IDX_94D4687F853CD175 ON competence');
        $this->addSql('DROP INDEX IDX_47CC8C92853CD175 ON action');
        
        // Drop columns
        $this->addSql('ALTER TABLE competence DROP quiz_id');
        $this->addSql('ALTER TABLE action DROP quiz_id');
    }

    public function down(Schema $schema): void
    {
        // Add columns back
        $this->addSql('ALTER TABLE competence ADD quiz_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE action ADD quiz_id INT DEFAULT NULL');
        
        // Update quiz_id values based on idmodule
        $this->addSql('UPDATE competence c JOIN quiz q ON c.idmodule = q.idmodule SET c.quiz_id = q.id');
        $this->addSql('UPDATE action a JOIN quiz q ON a.idmodule = q.idmodule SET a.quiz_id = q.id');
        
        // Add indexes
        $this->addSql('CREATE INDEX IDX_94D4687F853CD175 ON competence (quiz_id)');
        $this->addSql('CREATE INDEX IDX_47CC8C92853CD175 ON action (quiz_id)');
        
        // Add foreign key constraints
        $this->addSql('ALTER TABLE competence ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)');
    }
}
