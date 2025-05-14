<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250502131705 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064047EE5403C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404C5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CE6064047EE5403C ON reclamation
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CE606404C5697D6D ON reclamation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD user_id INT NOT NULL, ADD message LONGTEXT NOT NULL, ADD status VARCHAR(50) NOT NULL, ADD date DATETIME NOT NULL, ADD response LONGTEXT DEFAULT NULL, ADD response_date DATETIME DEFAULT NULL, DROP administrateur_id, DROP apprenant_id, DROP name, DROP email, DROP phone, DROP current_password, DROP new_password, CHANGE profile_image subject VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE606404A76ED395 ON reclamation (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_CE606404A76ED395 ON reclamation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD apprenant_id INT NOT NULL, ADD email VARCHAR(50) NOT NULL, ADD phone VARCHAR(50) NOT NULL, ADD current_password VARCHAR(50) NOT NULL, ADD new_password VARCHAR(50) NOT NULL, DROP message, DROP date, DROP response, DROP response_date, CHANGE user_id administrateur_id INT NOT NULL, CHANGE status name VARCHAR(50) NOT NULL, CHANGE subject profile_image VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064047EE5403C FOREIGN KEY (administrateur_id) REFERENCES administrateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id) ON UPDATE NO ACTION ON DELETE NO ACTION
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE6064047EE5403C ON reclamation (administrateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CE606404C5697D6D ON reclamation (apprenant_id)
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
