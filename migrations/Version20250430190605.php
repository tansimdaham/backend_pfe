<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250430190605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, quiz_id INT DEFAULT NULL, competence_id INT DEFAULT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, categorie_fr VARCHAR(250) DEFAULT NULL, categorie_en VARCHAR(250) DEFAULT NULL, INDEX IDX_47CC8C92853CD175 (quiz_id), INDEX IDX_47CC8C9215761DAB (competence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE competence (id INT AUTO_INCREMENT NOT NULL, quiz_id INT NOT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, categorie_fr VARCHAR(250) DEFAULT NULL, categorie_en VARCHAR(250) DEFAULT NULL, INDEX IDX_94D4687F853CD175 (quiz_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE sous_competence (id INT AUTO_INCREMENT NOT NULL, competence_id INT NOT NULL, nom_fr VARCHAR(250) NOT NULL, nom_en VARCHAR(250) NOT NULL, INDEX IDX_72AC3D9515761DAB (competence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C92853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action ADD CONSTRAINT FK_47CC8C9215761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence ADD CONSTRAINT FK_94D4687F853CD175 FOREIGN KEY (quiz_id) REFERENCES quiz (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE sous_competence ADD CONSTRAINT FK_72AC3D9515761DAB FOREIGN KEY (competence_id) REFERENCES competence (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP competence_id, DROP competence_nom_fr, DROP competence_nom_en, DROP comp_categorie_fr, DROP comp_categorie_en, DROP sous_competence_nom_fr, DROP sous_competence_nom_en, DROP action_nom_fr, DROP action_nom_en, DROP action_categorie_fr, DROP action_categorie_en
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92853CD175
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE action DROP FOREIGN KEY FK_47CC8C9215761DAB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE competence DROP FOREIGN KEY FK_94D4687F853CD175
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
            DROP TABLE sous_competence
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD competence_id INT NOT NULL, ADD competence_nom_fr VARCHAR(250) NOT NULL, ADD competence_nom_en VARCHAR(250) NOT NULL, ADD comp_categorie_fr VARCHAR(250) NOT NULL, ADD comp_categorie_en VARCHAR(250) NOT NULL, ADD sous_competence_nom_fr VARCHAR(250) NOT NULL, ADD sous_competence_nom_en VARCHAR(255) NOT NULL, ADD action_nom_fr VARCHAR(250) DEFAULT NULL, ADD action_nom_en VARCHAR(250) DEFAULT NULL, ADD action_categorie_fr VARCHAR(250) DEFAULT NULL, ADD action_categorie_en VARCHAR(250) DEFAULT NULL
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
