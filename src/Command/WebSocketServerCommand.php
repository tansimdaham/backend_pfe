<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\NotificationServer;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:websocket:server',
    description: 'Démarre le serveur WebSocket pour les notifications en temps réel',
)]
class WebSocketServerCommand extends Command
{
    private $notificationServer;
    private $entityManager;
    private $utilisateurRepository;
    private $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->logger = $logger;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Démarrage du serveur WebSocket pour les notifications');

        $port = 8080; // Port pour le serveur WebSocket
        
        $this->notificationServer = new NotificationServer(
            $this->entityManager,
            $this->utilisateurRepository,
            $this->logger
        );

        $io->info(sprintf('Serveur WebSocket démarré sur le port %d', $port));
        $io->info('Appuyez sur Ctrl+C pour arrêter le serveur');

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    $this->notificationServer
                )
            ),
            $port
        );

        $server->run();

        return Command::SUCCESS;
    }
}
