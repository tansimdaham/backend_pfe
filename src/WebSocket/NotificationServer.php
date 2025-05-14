<?php

namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UtilisateurRepository;
use Psr\Log\LoggerInterface;

class NotificationServer implements MessageComponentInterface
{
    protected $clients;
    protected $userConnections = [];
    protected $entityManager;
    protected $utilisateurRepository;
    protected $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository,
        LoggerInterface $logger
    ) {
        $this->clients = new \SplObjectStorage;
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->logger = $logger;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Stocker la nouvelle connexion
        $this->clients->attach($conn);
        $this->logger->info("Nouvelle connexion! ({$conn->resourceId})");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        // Authentification de l'utilisateur
        if (isset($data['type']) && $data['type'] === 'auth') {
            if (isset($data['token'])) {
                // Ici, vous devriez vérifier le token JWT
                // Pour simplifier, nous utilisons juste l'ID utilisateur
                $userId = $data['userId'] ?? null;
                
                if ($userId) {
                    // Associer cette connexion à l'utilisateur
                    $this->userConnections[$userId] = $from;
                    $this->logger->info("Utilisateur {$userId} authentifié");
                    
                    // Confirmer l'authentification
                    $from->send(json_encode([
                        'type' => 'auth_success',
                        'message' => 'Authentification réussie'
                    ]));
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // Détacher la connexion fermée
        $this->clients->detach($conn);
        
        // Supprimer l'utilisateur des connexions
        foreach ($this->userConnections as $userId => $connection) {
            if ($connection === $conn) {
                unset($this->userConnections[$userId]);
                $this->logger->info("Utilisateur {$userId} déconnecté");
                break;
            }
        }
        
        $this->logger->info("Connexion {$conn->resourceId} fermée");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->logger->error("Erreur: {$e->getMessage()}");
        $conn->close();
    }

    /**
     * Envoie une notification à un utilisateur spécifique
     */
    public function sendNotificationToUser(int $userId, array $notification)
    {
        if (isset($this->userConnections[$userId])) {
            $connection = $this->userConnections[$userId];
            $connection->send(json_encode([
                'type' => 'notification',
                'data' => $notification
            ]));
            $this->logger->info("Notification envoyée à l'utilisateur {$userId}");
            return true;
        }
        
        $this->logger->info("Utilisateur {$userId} non connecté, notification non envoyée");
        return false;
    }

    /**
     * Envoie une notification à tous les utilisateurs connectés
     */
    public function broadcastNotification(array $notification, array $excludeUserIds = [])
    {
        foreach ($this->userConnections as $userId => $connection) {
            if (!in_array($userId, $excludeUserIds)) {
                $connection->send(json_encode([
                    'type' => 'notification',
                    'data' => $notification
                ]));
            }
        }
        
        $this->logger->info("Notification diffusée à tous les utilisateurs");
        return true;
    }
}
