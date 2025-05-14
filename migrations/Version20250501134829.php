<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250501134829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action CHANGE quiz_id quiz_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD competence_id INT DEFAULT NULL, ADD competence_nom_fr VARCHAR(250) DEFAULT NULL, ADD competence_nom_en VARCHAR(250) DEFAULT NULL, ADD comp_categorie_fr VARCHAR(250) DEFAULT NULL, ADD comp_categorie_en VARCHAR(250) DEFAULT NULL, ADD sous_competence_nom_fr VARCHAR(250) DEFAULT NULL, ADD sous_competence_nom_en VARCHAR(250) DEFAULT NULL, ADD action_nom_fr VARCHAR(250) DEFAULT NULL, ADD action_nom_en VARCHAR(250) DEFAULT NULL, ADD action_categorie_fr VARCHAR(250) DEFAULT NULL, ADD action_categorie_en VARCHAR(250) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE action CHANGE quiz_id quiz_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP competence_id, DROP competence_nom_fr, DROP competence_nom_en, DROP comp_categorie_fr, DROP comp_categorie_en, DROP sous_competence_nom_fr, DROP sous_competence_nom_en, DROP action_nom_fr, DROP action_nom_en, DROP action_categorie_fr, DROP action_categorie_en
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
