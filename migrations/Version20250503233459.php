<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250503233459 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notification CHANGE certificat_id certificat_id INT DEFAULT NULL, CHANGE reclamation_id reclamation_id INT DEFAULT NULL, CHANGE messagerie_id messagerie_id INT DEFAULT NULL, CHANGE evaluation_id evaluation_id INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notification CHANGE certificat_id certificat_id INT NOT NULL, CHANGE reclamation_id reclamation_id INT NOT NULL, CHANGE messagerie_id messagerie_id INT NOT NULL, CHANGE evaluation_id evaluation_id INT NOT NULL
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
