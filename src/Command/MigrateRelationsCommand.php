<?php

namespace App\Command;

use App\Entity\Action;
use App\Entity\Competence;
use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-relations',
    description: 'Migre les relations basées sur idmodule vers les relations basées sur quiz_id',
)]
class MigrateRelationsCommand extends Command
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
        $io->title('Migration des relations basées sur idmodule vers les relations basées sur quiz_id');

        // Récupérer tous les quiz
        $quizzes = $this->entityManager->getRepository(Quiz::class)->findAll();
        $io->info(sprintf('Nombre de quiz à traiter : %d', count($quizzes)));

        // Créer un tableau associatif idmodule => quiz
        $quizByIdModule = [];
        foreach ($quizzes as $quiz) {
            $idModule = $quiz->getIDModule();
            if (!empty($idModule)) {
                $quizByIdModule[$idModule] = $quiz;
            }
        }

        // Migrer les compétences
        $competences = $this->entityManager->getRepository(Competence::class)->findAll();
        $io->info(sprintf('Nombre de compétences à traiter : %d', count($competences)));
        
        $competencesUpdated = 0;
        foreach ($competences as $competence) {
            $idModule = $competence->getIdmodule();
            if (!empty($idModule) && isset($quizByIdModule[$idModule])) {
                $quiz = $quizByIdModule[$idModule];
                $competence->setQuiz($quiz);
                $competencesUpdated++;
            }
        }
        
        $io->success(sprintf('Compétences mises à jour : %d', $competencesUpdated));

        // Migrer les actions
        $actions = $this->entityManager->getRepository(Action::class)->findAll();
        $io->info(sprintf('Nombre d\'actions à traiter : %d', count($actions)));
        
        $actionsUpdated = 0;
        foreach ($actions as $action) {
            $idModule = $action->getIdmodule();
            if (!empty($idModule) && isset($quizByIdModule[$idModule])) {
                $quiz = $quizByIdModule[$idModule];
                $action->setQuiz($quiz);
                $actionsUpdated++;
            }
        }
        
        $io->success(sprintf('Actions mises à jour : %d', $actionsUpdated));

        // Sauvegarder les modifications
        $this->entityManager->flush();
        $io->success('Migration des relations terminée avec succès');

        return Command::SUCCESS;
    }
}
