<?php

namespace App\Command;

use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-evaluation-idmodule',
    description: 'Update idmodule field in evaluation table from associated quiz',
)]
class UpdateEvaluationIdmoduleCommand extends Command
{
    private $evaluationRepository;
    private $entityManager;

    public function __construct(
        EvaluationRepository $evaluationRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct();
        $this->evaluationRepository = $evaluationRepository;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Updating idmodule field in evaluation table');
        
        // Get all evaluations
        $evaluations = $this->evaluationRepository->findAll();
        $count = count($evaluations);
        
        $io->progressStart($count);
        
        $updated = 0;
        
        foreach ($evaluations as $evaluation) {
            $quiz = $evaluation->getQuiz();
            
            if ($quiz && $quiz->getIDModule()) {
                $oldIdmodule = $evaluation->getIdmodule();
                $newIdmodule = $quiz->getIDModule();
                
                // Update only if needed
                if ($oldIdmodule !== $newIdmodule) {
                    $evaluation->setIdmodule($newIdmodule);
                    $updated++;
                }
            }
            
            $io->progressAdvance();
        }
        
        // Save changes
        $this->entityManager->flush();
        
        $io->progressFinish();
        
        $io->success(sprintf(
            'Successfully updated idmodule for %d out of %d evaluations.',
            $updated,
            $count
        ));
        
        return Command::SUCCESS;
    }
}
