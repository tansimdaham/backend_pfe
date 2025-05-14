<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240101000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table evaluation_detail pour stocker les détails des évaluations';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE evaluation_detail (id INT AUTO_INCREMENT NOT NULL, evaluation_id INT NOT NULL, competence_statuses JSON DEFAULT NULL, checked_sous_competences JSON DEFAULT NULL, checked_actions JSON DEFAULT NULL, main_value DOUBLE PRECISION DEFAULT NULL, surface_value DOUBLE PRECISION DEFAULT NULL, original_main_value DOUBLE PRECISION DEFAULT NULL, original_surface_value DOUBLE PRECISION DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D6E3F8A456C5646 (evaluation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE evaluation_detail ADD CONSTRAINT FK_D6E3F8A456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE evaluation_detail DROP FOREIGN KEY FK_D6E3F8A456C5646');
        $this->addSql('DROP TABLE evaluation_detail');
    }
}
