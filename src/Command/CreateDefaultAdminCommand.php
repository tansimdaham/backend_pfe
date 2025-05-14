<?php

namespace App\Command;

use App\Entity\Administrateur;
use App\Repository\AdministrateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-default-admin',
    description: 'Creates a default admin user if none exists',
)]
class CreateDefaultAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private AdministrateurRepository $administrateurRepository;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        AdministrateurRepository $administrateurRepository,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->administrateurRepository = $administrateurRepository;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si un administrateur existe déjà
        $adminCount = $this->administrateurRepository->count([]);

        if ($adminCount > 0) {
            $io->success('Un administrateur existe déjà dans le système.');
            return Command::SUCCESS;
        }

        // Créer un nouvel administrateur
        $admin = new Administrateur();
        $admin->setName('admin');
        $admin->setEmail('admin@quess360.tn');
        $admin->setPhone(12345678);
        $admin->setRole('administrateur');
        $admin->setIsApproved(true);

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123');
        $admin->setPassword($hashedPassword);

        // Persister l'administrateur
        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrateur par défaut créé avec succès!');
        $io->table(
            ['Email', 'Mot de passe'],
            [['admin@quess360.tn', 'admin123']]
        );

        return Command::SUCCESS;
    }
}
