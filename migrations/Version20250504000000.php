<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250504000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update idmodule in evaluation table from associated quiz';
    }

    public function up(Schema $schema): void
    {
        // Update idmodule values in evaluation table based on quiz relationship
        $this->addSql('UPDATE evaluation e JOIN quiz q ON e.quiz_id = q.id SET e.idmodule = q.idmodule WHERE e.idmodule IS NULL');
        
        // Add index for better performance
        $this->addSql('CREATE INDEX IDX_EVALUATION_IDMODULE ON evaluation (idmodule)');
    }

    public function down(Schema $schema): void
    {
        // Remove index
        $this->addSql('DROP INDEX IDX_EVALUATION_IDMODULE ON evaluation');
    }
}
