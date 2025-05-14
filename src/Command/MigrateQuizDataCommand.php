<?php

namespace App\Command;

use App\Entity\Action;
use App\Entity\Competence;
use App\Entity\Quiz;
use App\Entity\SousCompetence;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-quiz-data',
    description: 'Migre les données des quiz vers les nouvelles entités Competence, SousCompetence et Action',
)]
class MigrateQuizDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Migration des données des quiz vers les nouvelles entités');

        // Récupérer tous les quiz
        $quizzes = $this->entityManager->getRepository(Quiz::class)->findAll();
        $io->info(sprintf('Nombre de quiz à traiter : %d', count($quizzes)));

        // Grouper les quiz par IDModule
        $quizzesByIDModule = [];
        foreach ($quizzes as $quiz) {
            $idModule = $quiz->getIDModule();
            if (!isset($quizzesByIDModule[$idModule])) {
                $quizzesByIDModule[$idModule] = [];
            }
            $quizzesByIDModule[$idModule][] = $quiz;
        }

        $competencesCreated = 0;
        $sousCompetencesCreated = 0;
        $actionsCreated = 0;

        // Traiter chaque groupe de quiz par IDModule
        foreach ($quizzesByIDModule as $idModule => $quizzesGroup) {
            $io->section(sprintf('Traitement du groupe de quiz avec IDModule : %s', $idModule));
            
            // Récupérer le premier quiz du groupe comme référence
            $referenceQuiz = $quizzesGroup[0];
            
            // Créer un tableau pour stocker les compétences déjà traitées
            $competencesProcessed = [];
            
            // Traiter chaque quiz du groupe
            foreach ($quizzesGroup as $quiz) {
                // Vérifier si le quiz a une compétence
                if ($quiz->getCompetenceID() > 0) {
                    $competenceId = $quiz->getCompetenceID();
                    $competenceKey = $competenceId . '-' . $quiz->getCompetenceNomFR() . '-' . $quiz->getCompetenceNomEN();
                    
                    // Vérifier si cette compétence a déjà été traitée
                    if (!isset($competencesProcessed[$competenceKey])) {
                        // Créer une nouvelle compétence
                        $competence = new Competence();
                        $competence->setQuiz($referenceQuiz);
                        $competence->setNomFr($quiz->getCompetenceNomFR());
                        $competence->setNomEn($quiz->getCompetenceNomEN());
                        $competence->setCategorieFr($quiz->getCompCategorieFR());
                        $competence->setCategorieEn($quiz->getCompCategorieEN());
                        
                        $this->entityManager->persist($competence);
                        $competencesProcessed[$competenceKey] = $competence;
                        $competencesCreated++;
                        
                        $io->text(sprintf('Compétence créée : %s / %s', $competence->getNomFr(), $competence->getNomEn()));
                    }
                    
                    $competence = $competencesProcessed[$competenceKey];
                    
                    // Vérifier si le quiz a une sous-compétence
                    if (!empty($quiz->getSousCompetenceNomFR()) && !empty($quiz->getSousCompetenceNomEN())) {
                        $sousCompetenceKey = $quiz->getSousCompetenceNomFR() . '-' . $quiz->getSousCompetenceNomEN();
                        
                        // Vérifier si cette sous-compétence existe déjà pour cette compétence
                        $sousCompetenceExists = false;
                        foreach ($competence->getSousCompetences() as $sc) {
                            if ($sc->getNomFr() === $quiz->getSousCompetenceNomFR() && $sc->getNomEn() === $quiz->getSousCompetenceNomEN()) {
                                $sousCompetenceExists = true;
                                break;
                            }
                        }
                        
                        if (!$sousCompetenceExists) {
                            // Créer une nouvelle sous-compétence
                            $sousCompetence = new SousCompetence();
                            $sousCompetence->setCompetence($competence);
                            $sousCompetence->setNomFr($quiz->getSousCompetenceNomFR());
                            $sousCompetence->setNomEn($quiz->getSousCompetenceNomEN());
                            
                            $this->entityManager->persist($sousCompetence);
                            $sousCompetencesCreated++;
                            
                            $io->text(sprintf('Sous-compétence créée : %s / %s', $sousCompetence->getNomFr(), $sousCompetence->getNomEn()));
                        }
                    }
                    
                    // Vérifier si le quiz a une action liée à la compétence
                    if (!empty($quiz->getActionNomFR()) && !empty($quiz->getActionNomEN())) {
                        $actionKey = $quiz->getActionNomFR() . '-' . $quiz->getActionNomEN();
                        
                        // Vérifier si cette action existe déjà pour cette compétence
                        $actionExists = false;
                        foreach ($competence->getActions() as $act) {
                            if ($act->getNomFr() === $quiz->getActionNomFR() && $act->getNomEn() === $quiz->getActionNomEN()) {
                                $actionExists = true;
                                break;
                            }
                        }
                        
                        if (!$actionExists) {
                            // Créer une nouvelle action liée à la compétence
                            $action = new Action();
                            $action->setCompetence($competence);
                            $action->setNomFr($quiz->getActionNomFR());
                            $action->setNomEn($quiz->getActionNomEN());
                            $action->setCategorieFr($quiz->getActionCategorieFR());
                            $action->setCategorieEn($quiz->getActionCategorieEN());
                            
                            $this->entityManager->persist($action);
                            $actionsCreated++;
                            
                            $io->text(sprintf('Action créée (liée à la compétence) : %s / %s', $action->getNomFr(), $action->getNomEn()));
                        }
                    }
                } else if (!empty($quiz->getActionNomFR()) && !empty($quiz->getActionNomEN())) {
                    // Action liée directement au quiz (sans compétence)
                    $actionKey = $quiz->getActionNomFR() . '-' . $quiz->getActionNomEN();
                    
                    // Vérifier si cette action existe déjà pour ce quiz
                    $actionExists = false;
                    foreach ($referenceQuiz->getActions() as $act) {
                        if ($act->getNomFr() === $quiz->getActionNomFR() && $act->getNomEn() === $quiz->getActionNomEN()) {
                            $actionExists = true;
                            break;
                        }
                    }
                    
                    if (!$actionExists) {
                        // Créer une nouvelle action liée directement au quiz
                        $action = new Action();
                        $action->setQuiz($referenceQuiz);
                        $action->setNomFr($quiz->getActionNomFR());
                        $action->setNomEn($quiz->getActionNomEN());
                        $action->setCategorieFr($quiz->getActionCategorieFR());
                        $action->setCategorieEn($quiz->getActionCategorieEN());
                        
                        $this->entityManager->persist($action);
                        $actionsCreated++;
                        
                        $io->text(sprintf('Action créée (liée au quiz) : %s / %s', $action->getNomFr(), $action->getNomEn()));
                    }
                }
            }
            
            // Flush après chaque groupe de quiz
            $this->entityManager->flush();
        }
        
        $io->success([
            'Migration terminée avec succès',
            sprintf('%d compétences créées', $competencesCreated),
            sprintf('%d sous-compétences créées', $sousCompetencesCreated),
            sprintf('%d actions créées', $actionsCreated)
        ]);

        return Command::SUCCESS;
    }
}
