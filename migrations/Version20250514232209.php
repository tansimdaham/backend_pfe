<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514232209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE chatbot_conversation (id INT AUTO_INCREMENT NOT NULL, apprenant_id INT NOT NULL, user_message LONGTEXT NOT NULL, ai_response LONGTEXT NOT NULL, created_at DATETIME NOT NULL, context VARCHAR(255) DEFAULT NULL, INDEX IDX_764526E8C5697D6D (apprenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE chatbot_conversation ADD CONSTRAINT FK_764526E8C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE chatbot_conversation DROP FOREIGN KEY FK_764526E8C5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE chatbot_conversation
        SQL);
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
