<?php

namespace App\Service;

use App\Entity\Apprenant;
use App\Entity\ChatbotConversation;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

/**
 * Service dédié à la journalisation des interactions avec le chatbot
 */
class ChatbotLoggerService
{
    private LoggerInterface $logger;
    private RequestStack $requestStack;
    private Security $security;

    public function __construct(
        LoggerInterface $chatbotLogger,
        RequestStack $requestStack,
        Security $security
    ) {
        $this->logger = $chatbotLogger;
        $this->requestStack = $requestStack;
        $this->security = $security;
    }

    /**
     * Journalise une requête envoyée au chatbot
     *
     * @param string $message Message envoyé par l'utilisateur
     * @param Apprenant $apprenant Apprenant qui a envoyé le message
     * @param string|null $context Contexte de la conversation (optionnel)
     */
    public function logRequest(string $message, Apprenant $apprenant, ?string $context = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $ip = $request ? $request->getClientIp() : 'unknown';
        
        $this->logger->info('Chatbot request', [
            'user_id' => $apprenant->getId(),
            'user_email' => $apprenant->getEmail(),
            'message' => $message,
            'context' => $context,
            'ip' => $ip,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'session_id' => $request ? $request->getSession()->getId() : 'unknown'
        ]);
    }

    /**
     * Journalise une réponse du chatbot
     *
     * @param string $response Réponse du chatbot
     * @param Apprenant $apprenant Apprenant qui a reçu la réponse
     * @param float $processingTime Temps de traitement en secondes
     * @param bool $isError Indique si une erreur s'est produite
     */
    public function logResponse(string $response, Apprenant $apprenant, float $processingTime, bool $isError = false): void
    {
        $request = $this->requestStack->getCurrentRequest();
        
        $logData = [
            'user_id' => $apprenant->getId(),
            'user_email' => $apprenant->getEmail(),
            'response_length' => strlen($response),
            'processing_time' => $processingTime,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'session_id' => $request ? $request->getSession()->getId() : 'unknown'
        ];
        
        if ($isError) {
            $this->logger->error('Chatbot error response', array_merge($logData, [
                'error' => true,
                'response' => $response
            ]));
        } else {
            $this->logger->info('Chatbot response', array_merge($logData, [
                'response_preview' => substr($response, 0, 100) . (strlen($response) > 100 ? '...' : '')
            ]));
        }
    }

    /**
     * Journalise une erreur du chatbot
     *
     * @param \Exception $exception Exception qui s'est produite
     * @param string|null $message Message de l'utilisateur qui a provoqué l'erreur
     */
    public function logError(\Exception $exception, ?string $message = null): void
    {
        $user = $this->security->getUser();
        $userId = $user ? $user->getId() : 'unknown';
        $userEmail = $user ? $user->getEmail() : 'unknown';
        
        $this->logger->error('Chatbot error', [
            'user_id' => $userId,
            'user_email' => $userEmail,
            'message' => $message,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Journalise une action liée au chatbot (effacement de l'historique, etc.)
     *
     * @param string $action Action effectuée
     * @param array $data Données associées à l'action
     */
    public function logAction(string $action, array $data = []): void
    {
        $user = $this->security->getUser();
        $userId = $user ? $user->getId() : 'unknown';
        $userEmail = $user ? $user->getEmail() : 'unknown';
        
        $this->logger->info('Chatbot action', array_merge([
            'user_id' => $userId,
            'user_email' => $userEmail,
            'action' => $action,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ], $data));
    }

    /**
     * Journalise des statistiques d'utilisation du chatbot
     *
     * @param ChatbotConversation $conversation Conversation enregistrée
     * @param array $stats Statistiques à journaliser
     */
    public function logStats(ChatbotConversation $conversation, array $stats = []): void
    {
        $this->logger->info('Chatbot stats', array_merge([
            'conversation_id' => $conversation->getId(),
            'user_id' => $conversation->getApprenant()->getId(),
            'user_message_length' => strlen($conversation->getUserMessage()),
            'ai_response_length' => strlen($conversation->getAiResponse()),
            'timestamp' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
        ], $stats));
    }
}
