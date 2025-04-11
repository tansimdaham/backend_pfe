<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250411135309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE administrateur_cours (administrateur_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_E9A792837EE5403C (administrateur_id), INDEX IDX_E9A792837ECF78B0 (cours_id), PRIMARY KEY(administrateur_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE administrateur_apprenant (administrateur_id INT NOT NULL, apprenant_id INT NOT NULL, INDEX IDX_10A04E0A7EE5403C (administrateur_id), INDEX IDX_10A04E0AC5697D6D (apprenant_id), PRIMARY KEY(administrateur_id, apprenant_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE apprenant_cours (apprenant_id INT NOT NULL, cours_id INT NOT NULL, INDEX IDX_A3F510AC5697D6D (apprenant_id), INDEX IDX_A3F510A7ECF78B0 (cours_id), PRIMARY KEY(apprenant_id, cours_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE reclamation (id INT AUTO_INCREMENT NOT NULL, administrateur_id INT NOT NULL, apprenant_id INT NOT NULL, name VARCHAR(50) NOT NULL, email VARCHAR(50) NOT NULL, phone VARCHAR(50) NOT NULL, profile_image VARCHAR(255) NOT NULL, current_password VARCHAR(50) NOT NULL, new_password VARCHAR(50) NOT NULL, INDEX IDX_CE6064047EE5403C (administrateur_id), INDEX IDX_CE606404C5697D6D (apprenant_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_cours ADD CONSTRAINT FK_E9A792837EE5403C FOREIGN KEY (administrateur_id) REFERENCES administrateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_cours ADD CONSTRAINT FK_E9A792837ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_apprenant ADD CONSTRAINT FK_10A04E0A7EE5403C FOREIGN KEY (administrateur_id) REFERENCES administrateur (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_apprenant ADD CONSTRAINT FK_10A04E0AC5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant_cours ADD CONSTRAINT FK_A3F510AC5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant_cours ADD CONSTRAINT FK_A3F510A7ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE6064047EE5403C FOREIGN KEY (administrateur_id) REFERENCES administrateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation ADD CONSTRAINT FK_CE606404C5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE rclamation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant ADD certificta_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant ADD CONSTRAINT FK_C4EB462E35920590 FOREIGN KEY (certificta_id) REFERENCES certificat (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_C4EB462E35920590 ON apprenant (certificta_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE certificat ADD progression_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE certificat ADD CONSTRAINT FK_27448F77AF229C18 FOREIGN KEY (progression_id) REFERENCES progression (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_27448F77AF229C18 ON certificat (progression_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD formateur_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575155D8F51 FOREIGN KEY (formateur_id) REFERENCES formateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1323A575155D8F51 ON evaluation (formateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie ADD formateur_id INT NOT NULL, ADD apprenant_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie ADD CONSTRAINT FK_14E8F60C155D8F51 FOREIGN KEY (formateur_id) REFERENCES formateur (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie ADD CONSTRAINT FK_14E8F60CC5697D6D FOREIGN KEY (apprenant_id) REFERENCES apprenant (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_14E8F60C155D8F51 ON messagerie (formateur_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_14E8F60CC5697D6D ON messagerie (apprenant_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD certificat_id INT NOT NULL, ADD reclamation_id INT NOT NULL, ADD messagerie_id INT NOT NULL, ADD evaluation_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAFA55BACF FOREIGN KEY (certificat_id) REFERENCES certificat (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA2D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES reclamation (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA836C1031 FOREIGN KEY (messagerie_id) REFERENCES messagerie (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CAFA55BACF ON notification (certificat_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CA2D6BA2D9 ON notification (reclamation_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CA836C1031 ON notification (messagerie_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CA456C5646 ON notification (evaluation_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression ADD cours_id INT NOT NULL, ADD evaluation_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression ADD CONSTRAINT FK_D5B250737ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression ADD CONSTRAINT FK_D5B25073456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D5B250737ECF78B0 ON progression (cours_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_D5B25073456C5646 ON progression (evaluation_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD cours_id INT NOT NULL, ADD evaluation_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD CONSTRAINT FK_A412FA927ECF78B0 FOREIGN KEY (cours_id) REFERENCES cours (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz ADD CONSTRAINT FK_A412FA92456C5646 FOREIGN KEY (evaluation_id) REFERENCES evaluation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A412FA927ECF78B0 ON quiz (cours_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_A412FA92456C5646 ON quiz (evaluation_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA2D6BA2D9
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE rclamation (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, profile_image VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, current_password VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, new_password VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_cours DROP FOREIGN KEY FK_E9A792837EE5403C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_cours DROP FOREIGN KEY FK_E9A792837ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_apprenant DROP FOREIGN KEY FK_10A04E0A7EE5403C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE administrateur_apprenant DROP FOREIGN KEY FK_10A04E0AC5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant_cours DROP FOREIGN KEY FK_A3F510AC5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant_cours DROP FOREIGN KEY FK_A3F510A7ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE6064047EE5403C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reclamation DROP FOREIGN KEY FK_CE606404C5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE administrateur_cours
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE administrateur_apprenant
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE apprenant_cours
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reclamation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant DROP FOREIGN KEY FK_C4EB462E35920590
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_C4EB462E35920590 ON apprenant
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE apprenant DROP certificta_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE certificat DROP FOREIGN KEY FK_27448F77AF229C18
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX UNIQ_27448F77AF229C18 ON certificat
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE certificat DROP progression_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression DROP FOREIGN KEY FK_D5B250737ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression DROP FOREIGN KEY FK_D5B25073456C5646
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D5B250737ECF78B0 ON progression
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_D5B25073456C5646 ON progression
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE progression DROP cours_id, DROP evaluation_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAFA55BACF
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA836C1031
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA456C5646
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BF5476CAFA55BACF ON notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BF5476CA2D6BA2D9 ON notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BF5476CA836C1031 ON notification
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BF5476CA456C5646 ON notification
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP certificat_id, DROP reclamation_id, DROP messagerie_id, DROP evaluation_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575155D8F51
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1323A575155D8F51 ON evaluation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE evaluation DROP formateur_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA927ECF78B0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP FOREIGN KEY FK_A412FA92456C5646
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A412FA927ECF78B0 ON quiz
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_A412FA92456C5646 ON quiz
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE quiz DROP cours_id, DROP evaluation_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie DROP FOREIGN KEY FK_14E8F60C155D8F51
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie DROP FOREIGN KEY FK_14E8F60CC5697D6D
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_14E8F60C155D8F51 ON messagerie
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_14E8F60CC5697D6D ON messagerie
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE messagerie DROP formateur_id, DROP apprenant_id
        SQL);
    }
}
