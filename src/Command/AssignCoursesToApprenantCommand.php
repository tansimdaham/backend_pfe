<?php

namespace App\Command;

use App\Entity\Apprenant;
use App\Entity\Cours;
use App\Repository\ApprenantRepository;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:assign-courses',
    description: 'Assign courses to apprenants',
)]
class AssignCoursesToApprenantCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private ApprenantRepository $apprenantRepository;
    private CoursRepository $coursRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        ApprenantRepository $apprenantRepository,
        CoursRepository $coursRepository
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->apprenantRepository = $apprenantRepository;
        $this->coursRepository = $coursRepository;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Assigning courses to apprenants');

        // Get all apprenants
        $apprenants = $this->apprenantRepository->findAll();
        $io->info(sprintf('Found %d apprenants', count($apprenants)));

        // Get all courses
        $courses = $this->coursRepository->findAll();
        $io->info(sprintf('Found %d courses', count($courses)));

        if (count($courses) === 0) {
            $io->error('No courses found. Please create some courses first.');
            return Command::FAILURE;
        }

        $assignedCount = 0;

        // Assign all courses to all apprenants
        foreach ($apprenants as $apprenant) {
            $io->section(sprintf('Processing apprenant: %s (ID: %d)', $apprenant->getName(), $apprenant->getId()));
            
            $existingCourses = $apprenant->getCours();
            $existingCoursesCount = count($existingCourses);
            
            if ($existingCoursesCount > 0) {
                $io->note(sprintf('Apprenant already has %d courses', $existingCoursesCount));
            }

            $newCoursesCount = 0;
            foreach ($courses as $course) {
                // Check if apprenant already has this course
                $hasCourse = false;
                foreach ($existingCourses as $existingCourse) {
                    if ($existingCourse->getId() === $course->getId()) {
                        $hasCourse = true;
                        break;
                    }
                }

                if (!$hasCourse) {
                    $apprenant->addCour($course);
                    $newCoursesCount++;
                    $assignedCount++;
                }
            }

            if ($newCoursesCount > 0) {
                $io->text(sprintf('Added %d new courses to apprenant', $newCoursesCount));
            } else {
                $io->text('No new courses added to apprenant');
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('Successfully assigned %d courses to apprenants', $assignedCount));

        return Command::SUCCESS;
    }
}
