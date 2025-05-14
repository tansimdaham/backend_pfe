<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les champs d'authentification à l'entité Utilisateur
 */
final class Version20250428150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs nécessaires pour l\'authentification à l\'entité Utilisateur';
    }

    public function up(Schema $schema): void
    {
        // Modification de la table utilisateur
        $this->addSql('ALTER TABLE utilisateur CHANGE email email VARCHAR(50) NOT NULL');
        // L'index unique sur email existe déjà, donc on ne l'ajoute pas
        // $this->addSql('ALTER TABLE utilisateur ADD UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email)');
        $this->addSql('ALTER TABLE utilisateur CHANGE profile_image profile_image VARCHAR(255) DEFAULT NULL');
        // Les colonnes current_password et new_password n'existent pas, donc on ne les modifie pas
        // $this->addSql('ALTER TABLE utilisateur CHANGE current_password current_password VARCHAR(50) DEFAULT NULL');
        // $this->addSql('ALTER TABLE utilisateur CHANGE new_password new_password VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE password password VARCHAR(255) NOT NULL');

        // Vérifier si la colonne roles existe déjà
        $columns = $schema->getTable('utilisateur')->getColumns();
        $hasRoles = false;
        foreach ($columns as $column) {
            if ($column->getName() === 'roles') {
                $hasRoles = true;
                break;
            }
        }

        if (!$hasRoles) {
            $this->addSql('ALTER TABLE utilisateur ADD roles JSON NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Retour en arrière
        // L'index unique sur email existe déjà, donc on ne le supprime pas
        // $this->addSql('ALTER TABLE utilisateur DROP INDEX UNIQ_1D1C63B3E7927C74');
        $this->addSql('ALTER TABLE utilisateur CHANGE email email VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE utilisateur CHANGE profile_image profile_image VARCHAR(255) NOT NULL');
        // Les colonnes current_password et new_password n'existent pas, donc on ne les modifie pas
        // $this->addSql('ALTER TABLE utilisateur CHANGE current_password current_password VARCHAR(50) NOT NULL');
        // $this->addSql('ALTER TABLE utilisateur CHANGE new_password new_password VARCHAR(50) NOT NULL');

        // Vérifier si la colonne roles existe
        $columns = $schema->getTable('utilisateur')->getColumns();
        $hasRoles = false;
        foreach ($columns as $column) {
            if ($column->getName() === 'roles') {
                $hasRoles = true;
                break;
            }
        }

        if ($hasRoles) {
            $this->addSql('ALTER TABLE utilisateur DROP roles');
        }
    }
}
