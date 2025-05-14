<?php

namespace App\Command;

use App\Entity\Quiz;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-quiz-type',
    description: 'Corrige le type de tous les quiz pour utiliser "Evaluation" au lieu de "formation" ou "Training"',
)]
class FixQuizTypeCommand extends Command
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
        $io->title('Correction du type des quiz');

        // Récupérer tous les quiz
        $quizzes = $this->entityManager->getRepository(Quiz::class)->findAll();
        $io->info(sprintf('Nombre de quiz à traiter : %d', count($quizzes)));

        $updatedCount = 0;

        foreach ($quizzes as $quiz) {
            $oldType = $quiz->getType();
            
            // Si le type n'est pas "Evaluation", le corriger
            if ($oldType !== 'Evaluation') {
                $quiz->setType('Evaluation');
                $updatedCount++;
                $io->text(sprintf('Quiz ID %d (IDModule: %s) : Type changé de "%s" à "Evaluation"', 
                    $quiz->getId(), 
                    $quiz->getIDModule(), 
                    $oldType
                ));
            }
        }

        if ($updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('%d quiz ont été mis à jour', $updatedCount));
        } else {
            $io->success('Tous les quiz ont déjà le type "Evaluation"');
        }

        return Command::SUCCESS;
    }
}
