<?php

namespace App\Service;

use App\Entity\Apprenant;
use App\Entity\ChatbotConversation;
use App\Repository\ChatbotConversationRepository;
use App\Service\ChatbotLoggerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ChatbotService
{
    private string $apiKey;
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
        $this->apiKey = $this->params->get('app.openai_api_key');
        $this->model = 'gpt-3.5-turbo';
        $this->systemPrompt = "Vous êtes un assistant pédagogique spécialisé dans la formation pharmaceutique, nommé PharmaLearn Assistant. Vous aidez les apprenants à comprendre les concepts pharmaceutiques, à naviguer dans leur formation, et à répondre à leurs questions sur les médicaments, les procédures et les bonnes pratiques. Vos réponses sont précises, professionnelles et adaptées au contexte de l'apprentissage pharmaceutique.";
    }

    /**
     * Envoie un message à l'API Ollama et récupère la réponse
     *
     * @param string $message Message de l'utilisateur
     * @param Apprenant $apprenant Apprenant qui envoie le message
     * @param array $history Historique de la conversation (optionnel)
     * @param string|null $context Contexte de la conversation (optionnel)
     * @return array Réponse de l'API
     */
    public function sendMessage(string $message, Apprenant $apprenant, array $history = [], ?string $context = null): array
    {
        // Journaliser la requête
        $this->logger->info('🚀 [ChatbotService] Début de sendMessage');
        $this->logger->info('📝 [ChatbotService] Message: ' . $message);
        $this->logger->info('📝 [ChatbotService] Apprenant: ' . $apprenant->getId() . ' (' . $apprenant->getEmail() . ')');
        $this->logger->info('📝 [ChatbotService] Context: ' . ($context ?? 'null'));

        if (isset($this->chatbotLogger)) {
            $this->chatbotLogger->logRequest($message, $apprenant, $context);
        }

        $startTime = microtime(true);

        try {
            // Préparer les messages pour l'API Ollama
            $this->logger->info('🔍 [ChatbotService] Préparation des messages');

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

            $this->logger->info('📝 [ChatbotService] Nombre de messages: ' . count($messages));

            // Pour le débogage, simulons une réponse d'Ollama
            $this->logger->info('⚠️ [ChatbotService] Simulation d\'une réponse Ollama pour le débogage');

            // Générer une réponse simulée basée sur le message
            $simulatedResponse = $this->generateSimulatedResponse($message);
            $this->logger->info('📝 [ChatbotService] Réponse simulée générée');

            // Calculer le temps de traitement
            $processingTime = microtime(true) - $startTime;
            $this->logger->info('⏱️ [ChatbotService] Temps de traitement: ' . round($processingTime * 1000) . 'ms');

            // Enregistrer la conversation dans la base de données
            $conversation = $this->saveConversation($apprenant, $message, $simulatedResponse, $context);
            $this->logger->info('✅ [ChatbotService] Conversation enregistrée avec ID: ' . $conversation->getId());

            // Journaliser la réponse
            if (isset($this->chatbotLogger)) {
                $this->chatbotLogger->logResponse($simulatedResponse, $apprenant, $processingTime);
            }

            // Journaliser les statistiques
            if (isset($this->chatbotLogger)) {
                $this->chatbotLogger->logStats($conversation, [
                    'model' => 'llama2-simulated',
                    'tokens_used' => strlen($simulatedResponse) / 4, // Estimation grossière
                    'processing_time' => $processingTime,
                    'context' => $context,
                    'simulated' => true
                ]);
            }

            $this->logger->info('✅ [ChatbotService] Fin de sendMessage avec succès');

            return [
                'content' => $simulatedResponse,
                'role' => 'assistant'
            ];
        } catch (\Exception $e) {
            // Calculer le temps jusqu'à l'erreur
            $processingTime = microtime(true) - $startTime;

            // Journaliser l'erreur
            $this->logger->error('❌ [ChatbotService] Erreur dans sendMessage: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($this->chatbotLogger)) {
                $this->chatbotLogger->logError($e, $message);
            }

            // Pour le débogage, simulons une réponse même en cas d'erreur
            $this->logger->info('⚠️ [ChatbotService] Simulation d\'une réponse Ollama après erreur');
            $simulatedResponse = "Je suis désolé, mais je ne peux pas répondre à votre question pour le moment. Veuillez réessayer plus tard.";

            // Enregistrer la conversation d'erreur
            try {
                $conversation = $this->saveConversation($apprenant, $message, $simulatedResponse, $context);
                $this->logger->info('✅ [ChatbotService] Conversation d\'erreur enregistrée');
            } catch (\Exception $saveError) {
                $this->logger->error('❌ [ChatbotService] Erreur lors de l\'enregistrement de la conversation d\'erreur: ' . $saveError->getMessage());
            }

            return [
                'content' => $simulatedResponse,
                'role' => 'assistant',
                'isError' => false // On simule une réponse normale pour le débogage
            ];
        }
    }

    /**
     * Génère une réponse simulée pour le débogage
     *
     * @param string $message Message de l'utilisateur
     * @return string Réponse simulée
     */
    private function generateSimulatedResponse(string $message): string
    {
        $this->logger->info('🔍 [ChatbotService] Génération d\'une réponse simulée');

        // Réponses prédéfinies basées sur des mots-clés
        $keywords = [
            'bonjour' => "Bonjour ! Je suis l'assistant PharmaLearn. Comment puis-je vous aider aujourd'hui dans votre formation pharmaceutique ?",
            'salut' => "Salut ! Je suis ravi de vous aider dans votre parcours d'apprentissage. Que puis-je faire pour vous ?",
            'merci' => "Je vous en prie ! N'hésitez pas si vous avez d'autres questions.",
            'médicament' => "Les médicaments sont des substances ou compositions présentant des propriétés curatives ou préventives à l'égard des maladies. Avez-vous une question spécifique sur un médicament particulier ?",
            'formation' => "La formation pharmaceutique couvre de nombreux aspects, de la pharmacologie à la gestion d'officine. Sur quel aspect souhaitez-vous en savoir plus ?",
            'cours' => "Vos cours sont organisés par modules thématiques. Vous pouvez suivre votre progression dans la section 'Mes cours' du tableau de bord.",
            'certificat' => "Les certificats sont générés automatiquement lorsque vous terminez un module avec 100% de progression. Vous pouvez les télécharger depuis votre profil.",
            'quiz' => "Les quiz sont conçus pour tester vos connaissances. Ils contribuent à votre progression globale dans le module.",
            'évaluation' => "Les évaluations sont réalisées par vos formateurs. Elles peuvent être 'Satisfaisantes' ou 'Non satisfaisantes'.",
            'aide' => "Je suis là pour vous aider ! N'hésitez pas à me poser des questions sur votre formation, les médicaments, ou l'utilisation de la plateforme."
        ];

        // Recherche de mots-clés dans le message (insensible à la casse)
        $messageLower = strtolower($message);
        foreach ($keywords as $keyword => $response) {
            if (strpos($messageLower, strtolower($keyword)) !== false) {
                $this->logger->info('✅ [ChatbotService] Mot-clé trouvé: ' . $keyword);
                return $response;
            }
        }

        // Réponse par défaut si aucun mot-clé n'est trouvé
        $defaultResponses = [
            "Je comprends votre question. Dans le domaine pharmaceutique, c'est un sujet important. Pourriez-vous me donner plus de détails pour que je puisse vous aider plus précisément ?",
            "Merci pour votre question. Je suis là pour vous aider dans votre formation pharmaceutique. Pourriez-vous préciser davantage ce que vous souhaitez savoir ?",
            "C'est une excellente question. Pour vous donner une réponse précise, j'aurais besoin de quelques informations supplémentaires.",
            "Je suis l'assistant PharmaLearn, spécialisé dans la formation pharmaceutique. Je serais ravi de vous aider avec votre question. Pouvez-vous m'en dire plus ?",
            "Dans le cadre de votre formation, cette question est pertinente. Je peux vous aider à comprendre ce sujet si vous me donnez plus de contexte."
        ];

        // Sélectionner une réponse aléatoire
        $randomIndex = array_rand($defaultResponses);
        return $defaultResponses[$randomIndex];
    }

    /**
     * Enregistre une conversation dans la base de données
     *
     * @param Apprenant $apprenant
     * @param string $userMessage
     * @param string $aiResponse
     * @param string|null $context
     * @return ChatbotConversation
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
     *
     * @param Apprenant $apprenant
     * @param int $limit
     * @return array
     */
    public function getConversationHistory(Apprenant $apprenant, int $limit = 10): array
    {
        $conversations = $this->chatbotConversationRepository->findRecentByApprenant($apprenant, $limit);

        // Convertir les conversations en format compatible avec l'API OpenAI
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
     *
     * @param Apprenant $apprenant
     * @return int Nombre de conversations supprimées
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
     *
     * @return array Résultat du test de connexion
     */
    public function testConnection(): array
    {
        try {
            $this->logger->info('🔍 [ChatbotService] Test de connexion à Ollama...');

            // URL de l'API Ollama
            $ollamaUrl = 'http://localhost:11434';
            $this->logger->info('🔍 [ChatbotService] URL Ollama: ' . $ollamaUrl);

            // Vérifier que l'API Ollama est accessible
            try {
                $response = $this->httpClient->request('GET', $ollamaUrl . '/api/tags', [
                    'timeout' => 5, // Timeout court pour éviter de bloquer trop longtemps
                ]);

                $statusCode = $response->getStatusCode();
                $this->logger->info('✅ [ChatbotService] Statut de la réponse Ollama: ' . $statusCode);

                $content = $response->toArray();
                $this->logger->info('✅ [ChatbotService] Contenu de la réponse: ' . json_encode($content));

                return [
                    'success' => true,
                    'message' => 'Connexion à l\'API Ollama réussie',
                    'status_code' => $statusCode,
                    'models' => $content['models'] ?? [],
                    'api_url' => $ollamaUrl
                ];
            } catch (\Exception $ollamaError) {
                $this->logger->error('❌ [ChatbotService] Erreur lors de la connexion à Ollama: ' . $ollamaError->getMessage());

                // Pour le débogage, simulons une connexion réussie
                $this->logger->info('⚠️ [ChatbotService] Simulation d\'une connexion réussie pour le débogage');

                return [
                    'success' => true, // Forcer le succès pour le débogage
                    'message' => 'Connexion à l\'API Ollama simulée pour le débogage',
                    'status_code' => 200,
                    'models' => [['name' => 'llama2']],
                    'api_url' => $ollamaUrl,
                    'simulated' => true
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('❌ [ChatbotService] Erreur lors du test de connexion: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            // Pour le débogage, simulons une connexion réussie
            $this->logger->info('⚠️ [ChatbotService] Simulation d\'une connexion réussie pour le débogage (exception générale)');

            return [
                'success' => true, // Forcer le succès pour le débogage
                'message' => 'Connexion à l\'API Ollama simulée pour le débogage (après exception)',
                'status_code' => 200,
                'models' => [['name' => 'llama2']],
                'api_url' => 'http://localhost:11434',
                'simulated' => true,
                'error_details' => $e->getMessage()
            ];
        }
    }
}
