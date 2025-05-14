<?php

namespace App\Command;

use App\Entity\Quiz;
use App\Entity\Competence;
use App\Entity\Action;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:synchronize-idmodule',
    description: 'Synchronise les champs idmodule dans les tables Competence et Action avec les valeurs de IDModule dans Quiz',
)]
class SynchronizeIdmoduleCommand extends Command
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
        $io->title('Synchronisation des champs idmodule');

        // Récupérer tous les quiz
        $quizzes = $this->entityManager->getRepository(Quiz::class)->findAll();
        $io->info(sprintf('Nombre de quiz à traiter : %d', count($quizzes)));

        // Créer un tableau associatif des quiz par IDModule pour un accès rapide
        $quizByIdModule = [];
        foreach ($quizzes as $quiz) {
            $idModule = $quiz->getIDModule();
            if (!empty($idModule)) {
                $quizByIdModule[$idModule] = $quiz;
            }
        }

        // Synchroniser les compétences
        $competences = $this->entityManager->getRepository(Competence::class)->findAll();
        $io->info(sprintf('Nombre de compétences à traiter : %d', count($competences)));
        
        $competencesUpdated = 0;
        foreach ($competences as $competence) {
            $quiz = $competence->getQuiz();
            if ($quiz !== null && $quiz->getIDModule() !== null) {
                $oldIdModule = $competence->getIdmodule();
                $newIdModule = $quiz->getIDModule();
                
                if ($oldIdModule !== $newIdModule) {
                    $competence->setIdmodule($newIdModule);
                    $competencesUpdated++;
                    $io->text(sprintf('Compétence ID %d : idmodule mis à jour de "%s" à "%s"', 
                        $competence->getId(), 
                        $oldIdModule ?? 'null', 
                        $newIdModule
                    ));
                }
            }
        }

        // Synchroniser les actions
        $actions = $this->entityManager->getRepository(Action::class)->findAll();
        $io->info(sprintf('Nombre d\'actions à traiter : %d', count($actions)));
        
        $actionsUpdated = 0;
        foreach ($actions as $action) {
            $quiz = $action->getQuiz();
            if ($quiz !== null && $quiz->getIDModule() !== null) {
                $oldIdModule = $action->getIdmodule();
                $newIdModule = $quiz->getIDModule();
                
                if ($oldIdModule !== $newIdModule) {
                    $action->setIdmodule($newIdModule);
                    $actionsUpdated++;
                    $io->text(sprintf('Action ID %d : idmodule mis à jour de "%s" à "%s"', 
                        $action->getId(), 
                        $oldIdModule ?? 'null', 
                        $newIdModule
                    ));
                }
            }
        }

        if ($competencesUpdated > 0 || $actionsUpdated > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d compétences et %d actions ont été mises à jour', $competencesUpdated, $actionsUpdated));
        } else {
            $io->success('Tous les champs idmodule sont déjà synchronisés');
        }

        return Command::SUCCESS;
    }
}
