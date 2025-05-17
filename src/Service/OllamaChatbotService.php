<?php

namespace App\Service;

use App\Entity\Apprenant;
use App\Entity\ChatbotConversation;
use App\Repository\ChatbotConversationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaChatbotService
{
    private string $apiUrl;
    private string $model;
    private string $systemPrompt;

    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private ChatbotConversationRepository $chatbotConversationRepository,
        private LoggerInterface $logger,
        private ParameterBagInterface $params,
        private ChatbotLoggerService $chatbotLogger
    ) {
        // URL de l'API Ollama (par défaut: http://localhost:11434)
        $this->apiUrl = $this->params->get('app.ollama_api_url');
        // Modèle à utiliser (mistral, llama2, etc.)
        $this->model = $this->params->get('app.ollama_model');
        $this->systemPrompt = "Vous êtes un assistant pédagogique spécialisé dans la formation pharmaceutique, nommé PharmaLearn Assistant. Vous aidez les apprenants à comprendre les concepts pharmaceutiques, à naviguer dans leur formation, et à répondre à leurs questions sur les médicaments, les procédures et les bonnes pratiques. Vos réponses sont précises, professionnelles et adaptées au contexte de l'apprentissage pharmaceutique.";
    }

    /**
     * Envoie un message à l'API Ollama et récupère la réponse
     */
    public function sendMessage(string $message, Apprenant $apprenant, array $history = [], ?string $context = null): array
    {
        // Journaliser la requête
        $this->chatbotLogger->logRequest($message, $apprenant, $context);

        $startTime = microtime(true);

        try {
            // Préparer les messages pour l'API Ollama
            $messages = [
                [
                    'role' => 'system',
                    'content' => $this->systemPrompt
                ]
            ];

            // Ajouter l'historique de la conversation
            foreach ($history as $item) {
                $messages[] = [
                    'role' => $item['role'],
                    'content' => $item['content']
                ];
            }

            // Ajouter le message de l'utilisateur
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];

            // Journaliser les détails de la requête
            $this->logger->info('Détails de la requête Ollama', [
                'url' => $this->apiUrl . '/api/chat',
                'model' => $this->model,
                'message_count' => count($messages)
            ]);

            try {
                // Appeler l'API Ollama
                $response = $this->httpClient->request('POST', $this->apiUrl . '/api/chat', [
                    'json' => [
                        'model' => $this->model,
                        'messages' => $messages,
                        'stream' => false
                    ],
                    'verify_peer' => false,
                    'verify_host' => false
                ]);
            } catch (\Exception $requestException) {
                $this->logger->error('Erreur lors de la requête à Ollama: ' . $requestException->getMessage(), [
                    'exception' => get_class($requestException),
                    'url' => $this->apiUrl . '/api/chat',
                    'model' => $this->model
                ]);
                throw $requestException;
            }

            $data = $response->toArray();
            $aiResponse = $data['message']['content'] ?? 'Désolé, je n\'ai pas pu générer de réponse.';

            // Calculer le temps de traitement
            $processingTime = microtime(true) - $startTime;

            // Enregistrer la conversation dans la base de données
            $conversation = $this->saveConversation($apprenant, $message, $aiResponse, $context);

            // Journaliser la réponse
            $this->chatbotLogger->logResponse($aiResponse, $apprenant, $processingTime);

            // Journaliser les statistiques
            $this->chatbotLogger->logStats($conversation, [
                'model' => $this->model,
                'processing_time' => $processingTime,
                'context' => $context
            ]);

            return [
                'content' => $aiResponse,
                'role' => 'assistant'
            ];
        } catch (\Exception $e) {
            // Calculer le temps jusqu'à l'erreur
            $processingTime = microtime(true) - $startTime;

            // Journaliser l'erreur
            $this->chatbotLogger->logError($e, $message);
            $this->logger->error('Erreur lors de la communication avec l\'API Ollama: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Journaliser la réponse d'erreur
            $errorMessage = 'Désolé, je rencontre des difficultés à me connecter. Veuillez réessayer plus tard.';
            $this->chatbotLogger->logResponse($errorMessage, $apprenant, $processingTime, true);

            // Retourner un message d'erreur
            return [
                'content' => $errorMessage,
                'role' => 'assistant',
                'isError' => true
            ];
        }
    }

    /**
     * Enregistre une conversation dans la base de données
     */
    private function saveConversation(Apprenant $apprenant, string $userMessage, string $aiResponse, ?string $context = null): ChatbotConversation
    {
        $conversation = new ChatbotConversation();
        $conversation->setApprenant($apprenant);
        $conversation->setUserMessage($userMessage);
        $conversation->setAiResponse($aiResponse);
        $conversation->setContext($context);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        // Journaliser l'action de sauvegarde
        $this->chatbotLogger->logAction('save_conversation', [
            'conversation_id' => $conversation->getId(),
            'user_id' => $apprenant->getId(),
            'context' => $context
        ]);

        return $conversation;
    }

    /**
     * Récupère l'historique des conversations pour un apprenant
     */
    public function getConversationHistory(Apprenant $apprenant, int $limit = 10): array
    {
        $conversations = $this->chatbotConversationRepository->findRecentByApprenant($apprenant, $limit);

        // Convertir les conversations en format compatible avec l'API
        $history = [];
        foreach (array_reverse($conversations) as $conversation) {
            $history[] = [
                'role' => 'user',
                'content' => $conversation->getUserMessage()
            ];
            $history[] = [
                'role' => 'assistant',
                'content' => $conversation->getAiResponse()
            ];
        }

        return $history;
    }

    /**
     * Supprime toutes les conversations d'un apprenant
     */
    public function clearConversationHistory(Apprenant $apprenant): int
    {
        $count = $this->chatbotConversationRepository->deleteAllForApprenant($apprenant);

        // Journaliser l'action de suppression
        $this->chatbotLogger->logAction('clear_history', [
            'user_id' => $apprenant->getId(),
            'deleted_count' => $count
        ]);

        return $count;
    }

    /**
     * Teste la connexion à l'API Ollama
     */
    public function testConnection(): array
    {
        try {
            // Vérifier que le serveur Ollama est en cours d'exécution
            $response = $this->httpClient->request('GET', $this->apiUrl . '/api/tags', [
                'verify_peer' => false,
                'verify_host' => false
            ]);

            $statusCode = $response->getStatusCode();
            $content = $response->toArray();

            return [
                'success' => true,
                'message' => 'Connexion à Ollama réussie',
                'status_code' => $statusCode,
                'models' => $content['models'] ?? [],
                'api_url' => $this->apiUrl
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du test de connexion à Ollama: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la connexion à Ollama',
                'error' => $e->getMessage(),
                'api_url' => $this->apiUrl
            ];
        }
    }
}
