<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:test-email',
    description: 'Test email sending functionality',
)]
class TestEmailCommand extends Command
{
    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('');
        $output->writeln('<bg=blue;fg=white>                                                 </>');
        $output->writeln('<bg=blue;fg=white>          TEST D\'ENVOI D\'EMAILS                 </>');
        $output->writeln('<bg=blue;fg=white>                                                 </>');
        $output->writeln('');

        // Afficher la configuration actuelle
        $mailerDsn = $_ENV['MAILER_DSN'] ?? 'Non défini';
        $output->writeln('<fg=yellow>Configuration actuelle du mailer:</>');
        $output->writeln('MAILER_DSN: ' . $mailerDsn);
        $output->writeln('');

        // Créer un utilisateur de test
        $testEmail = 'test@example.com';
        $user = new class($testEmail) extends Utilisateur {
            public function __construct(string $email) {
                $this->setEmail($email);
                $this->setName('Test User');
            }
        };

        $output->writeln('<fg=yellow>Destinataire de test:</>');
        $output->writeln('Email: ' . $user->getEmail());
        $output->writeln('Nom: ' . $user->getName());
        $output->writeln('');

        // Tester l'envoi d'un email d'approbation
        $output->writeln('<fg=yellow>Test #1: Email d\'approbation</>');
        try {
            $output->writeln('Envoi de l\'email d\'approbation...');
            $start = microtime(true);
            $this->emailService->sendApprovalEmail($user);
            $duration = round((microtime(true) - $start) * 1000);
            $output->writeln('<fg=green>✅ Email d\'approbation envoyé avec succès!</>');
            $output->writeln('<fg=green>✅ Temps d\'envoi: ' . $duration . ' ms</>');
        } catch (\Exception $e) {
            $output->writeln('<fg=red>❌ Erreur lors de l\'envoi de l\'email d\'approbation:</>');
            $output->writeln('<fg=red>❌ ' . $e->getMessage() . '</>');

            // Vérifier si c'est dû à la configuration null://null
            if (strpos($e->getMessage(), 'null://null') !== false) {
                $output->writeln('');
                $output->writeln('<fg=blue>ℹ️ INFORMATION:</>');
                $output->writeln('<fg=blue>La configuration MAILER_DSN est définie sur "null://null".</>');
                $output->writeln('<fg=blue>Les emails ne sont pas réellement envoyés.</>');
                $output->writeln('<fg=blue>Pour envoyer des emails réels, modifiez le fichier .env avec une configuration valide:</>');
                $output->writeln('  - SMTP: MAILER_DSN=smtp://user:pass@smtp.example.com:port');
                $output->writeln('  - Gmail: MAILER_DSN=gmail://username:password@default');
                $output->writeln('  - Sendmail: MAILER_DSN=sendmail://default');
                $output->writeln('  - Pour le développement: MAILER_DSN=smtp://localhost:1025');
            }
        }
        $output->writeln('');

        // Tester l'envoi d'un email de rejet
        $output->writeln('<fg=yellow>Test #2: Email de rejet</>');
        $rejectReason = 'Ceci est un test de rejet';
        try {
            $output->writeln('Envoi de l\'email de rejet...');
            $output->writeln('Raison du rejet: ' . $rejectReason);
            $start = microtime(true);
            $this->emailService->sendRejectionEmail($user, $rejectReason);
            $duration = round((microtime(true) - $start) * 1000);
            $output->writeln('<fg=green>✅ Email de rejet envoyé avec succès!</>');
            $output->writeln('<fg=green>✅ Temps d\'envoi: ' . $duration . ' ms</>');
        } catch (\Exception $e) {
            $output->writeln('<fg=red>❌ Erreur lors de l\'envoi de l\'email de rejet:</>');
            $output->writeln('<fg=red>❌ ' . $e->getMessage() . '</>');
        }
        $output->writeln('');

        $output->writeln('<bg=blue;fg=white>                                                 </>');
        $output->writeln('<bg=blue;fg=white>          TEST TERMINÉ                           </>');
        $output->writeln('<bg=blue;fg=white>                                                 </>');
        $output->writeln('');

        return Command::SUCCESS;
    }
}
