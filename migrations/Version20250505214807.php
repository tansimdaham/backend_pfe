<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250505214807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, journee_entiere TINYINT(1) NOT NULL, categorie VARCHAR(50) NOT NULL, couleur VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE evenement_administrateur (evenement_id INT NOT NULL, administrateur_id INT NOT NULL, INDEX IDX_9A0E2964FD02F13 (evenement_id), INDEX IDX_9A0E29647EE5403C (administrateur_id), PRIMARY KEY(evenement_id, administrateur_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement_administrateur ADD CONSTRAINT FK_9A0E2964FD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement_administrateur ADD CONSTRAINT FK_9A0E29647EE5403C FOREIGN KEY (administrateur_id) REFERENCES administrateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD evenement_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CAFD02F13 ON notification (evenement_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAFD02F13
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement_administrateur DROP FOREIGN KEY FK_9A0E2964FD02F13
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evenement_administrateur DROP FOREIGN KEY FK_9A0E29647EE5403C
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evenement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE evenement_administrateur
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BF5476CAFD02F13 ON notification
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP evenement_id
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
