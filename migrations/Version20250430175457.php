<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250430175457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, competence_id INT DEFAULT NULL, quiz_id INT DEFAULT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, categorie_fr VARCHAR(250) DEFAULT NULL, categorie_en VARCHAR(250) DEFAULT NULL, INDEX IDX_47CC8C9215761DAB (competence_id), INDEX IDX_47CC8C92853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, categorie_fr VARCHAR(250) DEFAULT NULL, categorie_en VARCHAR(250) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE quiz_competence (quiz_id INT NOT NULL, competence_id INT NOT NULL, INDEX IDX_DDBB4F43853CD175 (quiz_id), INDEX IDX_DDBB4F4315761DAB (competence_id), PRIMARY KEY(quiz_id, competence_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sous_competence (id INT AUTO_INCREMENT NOT NULL, competence_id INT NOT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, INDEX IDX_72AC3D9515761DAB (competence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C9215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_competence ADD CONSTRAINT FK_DDBB4F43853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_competence ADD CONSTRAINT FK_DDBB4F4315761DAB FOREIGN KEY (competence_id) REFERENCES competence (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_competence ADD CONSTRAINT FK_72AC3D9515761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9215761DAB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_competence DROP FOREIGN KEY FK_DDBB4F43853CD175
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz_competence DROP FOREIGN KEY FK_DDBB4F4315761DAB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_competence DROP FOREIGN KEY FK_72AC3D9515761DAB
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE action
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE competence
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE quiz_competence
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE sous_competence
        SQL);
    }
}
