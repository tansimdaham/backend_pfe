<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour transférer les données des quiz vers les nouvelles entités
 */
final class Version20250430210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transfert des données des quiz vers les nouvelles entités Competence, SousCompetence et Action';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les tables existent
        $tables = $this->connection->getSchemaManager()->listTableNames();
        if (!in_array('competence', $tables) || !in_array('sous_competence', $tables) || !in_array('action', $tables)) {
            $this->write('Les tables competence, sous_competence ou action n\'existent pas. Migration annulée.');
            return;
        }

        // Récupérer les données des quiz
        $quizzes = $this->connection->fetchAllAssociative('SELECT * FROM quiz');
        $this->write(sprintf('Nombre de quiz à traiter : %d', count($quizzes)));

        $competencesCreated = 0;
        $sousCompetencesCreated = 0;
        $actionsCreated = 0;

        // Traiter chaque quiz individuellement
        foreach ($quizzes as $quiz) {
            $quizId = $quiz['id'];
            $this->write(sprintf('Traitement du quiz ID : %s', $quizId));

            // Créer un tableau pour stocker les compétences déjà traitées
            $competencesProcessed = [];
            // Vérifier si le quiz a une compétence
            if (isset($quiz['Competence_ID']) && $quiz['Competence_ID'] > 0) {
                $competenceId = $quiz['Competence_ID'];
                $competenceKey = $competenceId . '-' . $quiz['Competence_Nom_FR'] . '-' . $quiz['Competence_Nom_EN'];

                // Vérifier si cette compétence a déjà été traitée
                if (!isset($competencesProcessed[$competenceKey])) {
                    // Créer une nouvelle compétence
                    $this->connection->executeStatement(
                        'INSERT INTO competence (quiz_id, nom_fr, nom_en, categorie_fr, categorie_en) VALUES (?, ?, ?, ?, ?)',
                        [
                            $quizId,
                            $quiz['Competence_Nom_FR'],
                            $quiz['Competence_Nom_EN'],
                            $quiz['Comp_Categorie_FR'],
                            $quiz['Comp_Categorie_EN']
                        ]
                    );

                    $newCompetenceId = $this->connection->lastInsertId();
                    $competencesProcessed[$competenceKey] = $newCompetenceId;
                    $competencesCreated++;

                    $this->write(sprintf('Compétence créée : %s / %s (ID: %s)', $quiz['Competence_Nom_FR'], $quiz['Competence_Nom_EN'], $newCompetenceId));

                    // Vérifier si le quiz a une sous-compétence
                    if (!empty($quiz['SousCompetence_Nom_FR']) && !empty($quiz['SousCompetence_Nom_EN'])) {
                        // Créer une nouvelle sous-compétence
                        $this->connection->executeStatement(
                            'INSERT INTO sous_competence (competence_id, nom_fr, nom_en) VALUES (?, ?, ?)',
                            [
                                $newCompetenceId,
                                $quiz['SousCompetence_Nom_FR'],
                                $quiz['SousCompetence_Nom_EN']
                            ]
                        );

                        $sousCompetencesCreated++;
                        $this->write(sprintf('Sous-compétence créée : %s / %s', $quiz['SousCompetence_Nom_FR'], $quiz['SousCompetence_Nom_EN']));
                    }

                    // Vérifier si le quiz a une action liée à la compétence
                    if (!empty($quiz['Action_Nom_FR']) && !empty($quiz['Action_Nom_EN'])) {
                        // Créer une nouvelle action liée à la compétence
                        $this->connection->executeStatement(
                            'INSERT INTO action (competence_id, nom_fr, nom_en, categorie_fr, categorie_en) VALUES (?, ?, ?, ?, ?)',
                            [
                                $newCompetenceId,
                                $quiz['Action_Nom_FR'],
                                $quiz['Action_Nom_EN'],
                                $quiz['Action_Categorie_FR'],
                                $quiz['Action_Categorie_EN']
                            ]
                        );

                        $actionsCreated++;
                        $this->write(sprintf('Action créée (liée à la compétence) : %s / %s', $quiz['Action_Nom_FR'], $quiz['Action_Nom_EN']));
                    }
                } else {
                    $existingCompetenceId = $competencesProcessed[$competenceKey];

                    // Vérifier si le quiz a une sous-compétence
                    if (!empty($quiz['SousCompetence_Nom_FR']) && !empty($quiz['SousCompetence_Nom_EN'])) {
                        // Vérifier si cette sous-compétence existe déjà pour cette compétence
                        $existingSousCompetence = $this->connection->fetchAssociative(
                            'SELECT id FROM sous_competence WHERE competence_id = ? AND nom_fr = ? AND nom_en = ?',
                            [
                                $existingCompetenceId,
                                $quiz['SousCompetence_Nom_FR'],
                                $quiz['SousCompetence_Nom_EN']
                            ]
                        );

                        if (!$existingSousCompetence) {
                            // Créer une nouvelle sous-compétence
                            $this->connection->executeStatement(
                                'INSERT INTO sous_competence (competence_id, nom_fr, nom_en) VALUES (?, ?, ?)',
                                [
                                    $existingCompetenceId,
                                    $quiz['SousCompetence_Nom_FR'],
                                    $quiz['SousCompetence_Nom_EN']
                                ]
                            );

                            $sousCompetencesCreated++;
                            $this->write(sprintf('Sous-compétence créée : %s / %s', $quiz['SousCompetence_Nom_FR'], $quiz['SousCompetence_Nom_EN']));
                        }
                    }

                    // Vérifier si le quiz a une action liée à la compétence
                    if (!empty($quiz['Action_Nom_FR']) && !empty($quiz['Action_Nom_EN'])) {
                        // Vérifier si cette action existe déjà pour cette compétence
                        $existingAction = $this->connection->fetchAssociative(
                            'SELECT id FROM action WHERE competence_id = ? AND nom_fr = ? AND nom_en = ?',
                            [
                                $existingCompetenceId,
                                $quiz['Action_Nom_FR'],
                                $quiz['Action_Nom_EN']
                            ]
                        );

                        if (!$existingAction) {
                            // Créer une nouvelle action liée à la compétence
                            $this->connection->executeStatement(
                                'INSERT INTO action (competence_id, nom_fr, nom_en, categorie_fr, categorie_en) VALUES (?, ?, ?, ?, ?)',
                                [
                                    $existingCompetenceId,
                                    $quiz['Action_Nom_FR'],
                                    $quiz['Action_Nom_EN'],
                                    $quiz['Action_Categorie_FR'],
                                    $quiz['Action_Categorie_EN']
                                ]
                            );

                            $actionsCreated++;
                            $this->write(sprintf('Action créée (liée à la compétence) : %s / %s', $quiz['Action_Nom_FR'], $quiz['Action_Nom_EN']));
                        }
                    }
                }
            } else if (!empty($quiz['Action_Nom_FR']) && !empty($quiz['Action_Nom_EN'])) {
                // Action liée directement au quiz (sans compétence)

                // Vérifier si cette action existe déjà pour ce quiz
                $existingAction = $this->connection->fetchAssociative(
                    'SELECT id FROM action WHERE quiz_id = ? AND nom_fr = ? AND nom_en = ?',
                    [
                        $quizId,
                        $quiz['Action_Nom_FR'],
                        $quiz['Action_Nom_EN']
                    ]
                );

                if (!$existingAction) {
                    // Créer une nouvelle action liée directement au quiz
                    $this->connection->executeStatement(
                        'INSERT INTO action (quiz_id, nom_fr, nom_en, categorie_fr, categorie_en) VALUES (?, ?, ?, ?, ?)',
                        [
                            $quizId,
                            $quiz['Action_Nom_FR'],
                            $quiz['Action_Nom_EN'],
                            $quiz['Action_Categorie_FR'],
                            $quiz['Action_Categorie_EN']
                        ]
                    );

                    $actionsCreated++;
                    $this->write(sprintf('Action créée (liée au quiz) : %s / %s', $quiz['Action_Nom_FR'], $quiz['Action_Nom_EN']));
                }
            }
        }

        $this->write(sprintf('Migration terminée avec succès'));
        $this->write(sprintf('%d compétences créées', $competencesCreated));
        $this->write(sprintf('%d sous-compétences créées', $sousCompetencesCreated));
        $this->write(sprintf('%d actions créées', $actionsCreated));
    }

    public function down(Schema $schema): void
    {
        // Cette migration ne peut pas être annulée
        $this->write('Cette migration ne peut pas être annulée');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
