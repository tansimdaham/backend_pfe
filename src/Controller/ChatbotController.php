<?php

namespace App\Controller;

use App\Entity\Apprenant;
use App\Entity\ChatbotConversation;
use App\Repository\ApprenantRepository;
use App\Repository\ChatbotConversationRepository;
use App\Service\ChatbotLoggerService;
use App\Service\ChatbotService;
use App\Service\OllamaChatbotService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/chatbot')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ApprenantRepository $apprenantRepository,
        private ChatbotConversationRepository $chatbotConversationRepository,
        private OllamaChatbotService $chatbotService,
        private Security $security,
        private SerializerInterface $serializer,
        private ChatbotLoggerService $chatbotLogger,
        private LoggerInterface $logger
    ) {}

    /**
     * Envoie un message au chatbot et récupère la réponse
     */
    #[Route('/message', name: 'api_chatbot_message', methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        try {
            // Récupérer les données de la requête
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            $context = $data['context'] ?? null;

            // Récupérer l'utilisateur connecté
            $user = $this->security->getUser();
            if (!$user) {
                $this->chatbotLogger->logAction('authentication_error', [
                    'message' => 'Tentative d\'accès au chatbot sans authentification',
                    'ip' => $request->getClientIp()
                ]);
                return $this->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            // Vérifier que l'utilisateur est un apprenant
            $apprenant = $this->apprenantRepository->findOneBy(['id' => $user->getId()]);
            if (!$apprenant) {
                $this->chatbotLogger->logAction('authorization_error', [
                    'user_id' => $user->getId(),
                    'user_role' => $user->getRole(),
                    'message' => 'Tentative d\'accès au chatbot par un non-apprenant'
                ]);
                return $this->json(['error' => 'Seuls les apprenants peuvent utiliser le chatbot'], 403);
            }

            // Récupérer l'historique récent des conversations
            $history = $this->chatbotService->getConversationHistory($apprenant, 5);
            $this->logger->info('Historique récupéré', ['count' => count($history)]);

            try {
                // Vérifier que Ollama est accessible
                $connectionTest = $this->chatbotService->testConnection();
                if (!$connectionTest['success']) {
                    $this->logger->error('Échec du test de connexion à Ollama', $connectionTest);
                    return $this->json([
                        'content' => 'Désolé, je ne peux pas me connecter au service de chatbot en ce moment. Veuillez réessayer plus tard.',
                        'role' => 'assistant',
                        'isError' => true
                    ], 500);
                }

                $this->logger->info('Connexion à Ollama réussie', [
                    'api_url' => $connectionTest['api_url'],
                    'models_count' => count($connectionTest['models'] ?? [])
                ]);

                // Envoyer le message au service de chatbot
                $response = $this->chatbotService->sendMessage($message, $apprenant, $history, $context);
                $this->logger->info('Réponse reçue de Ollama', [
                    'content_length' => strlen($response['content'] ?? ''),
                    'is_error' => isset($response['isError']) ? $response['isError'] : false
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'envoi du message à Ollama', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                return $this->json([
                    'content' => 'Désolé, une erreur est survenue lors de la communication avec le service de chatbot. Veuillez réessayer plus tard.',
                    'role' => 'assistant',
                    'isError' => true,
                    'error_details' => $e->getMessage()
                ], 500);
            }

            return $this->json($response);
        } catch (\Exception $e) {
            // Journaliser l'erreur
            $this->chatbotLogger->logError($e, $message ?? null);

            return $this->json([
                'error' => 'Une erreur est survenue: ' . $e->getMessage(),
                'content' => 'Désolé, une erreur est survenue lors du traitement de votre demande.',
                'role' => 'assistant',
                'isError' => true
            ], 500);
        }
    }

    /**
     * Récupère l'historique des conversations pour l'apprenant connecté
     */
    #[Route('/history', name: 'api_chatbot_history', methods: ['GET'])]
    public function getHistory(Request $request): JsonResponse
    {
        try {
            // Récupérer l'utilisateur connecté
            $user = $this->security->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            // Vérifier que l'utilisateur est un apprenant
            $apprenant = $this->apprenantRepository->findOneBy(['id' => $user->getId()]);
            if (!$apprenant) {
                return $this->json(['error' => 'Seuls les apprenants peuvent accéder à l\'historique du chatbot'], 403);
            }

            // Récupérer les conversations
            $limit = $request->query->getInt('limit', 20);
            $conversations = $this->chatbotConversationRepository->findByApprenant($apprenant, $limit);

            // Formater les conversations pour le frontend
            $formattedConversations = [];
            foreach ($conversations as $conversation) {
                $formattedConversations[] = [
                    'id' => $conversation->getId(),
                    'userMessage' => [
                        'content' => $conversation->getUserMessage(),
                        'role' => 'user',
                        'time' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
                    ],
                    'aiResponse' => [
                        'content' => $conversation->getAiResponse(),
                        'role' => 'assistant',
                        'time' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
                    ],
                    'context' => $conversation->getContext(),
                    'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            return $this->json(['conversations' => $formattedConversations]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Une erreur est survenue: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Efface l'historique des conversations pour l'apprenant connecté
     */
    #[Route('/history/clear', name: 'api_chatbot_clear_history', methods: ['POST'])]
    public function clearHistory(Request $request): JsonResponse
    {
        try {
            // Journaliser le début de la requête
            $this->logger->info('Début de la requête clearHistory');

            // Récupérer l'utilisateur connecté
            $user = $this->security->getUser();
            if (!$user) {
                $this->chatbotLogger->logAction('authentication_error', [
                    'message' => 'Tentative d\'effacement de l\'historique sans authentification',
                    'ip' => $request->getClientIp()
                ]);
                $this->logger->error('Utilisateur non authentifié');
                return $this->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            $this->logger->info('Utilisateur authentifié: ' . $user->getId() . ' (' . $user->getEmail() . ')');

            // Vérifier que l'utilisateur est un apprenant
            $apprenant = $this->apprenantRepository->findOneBy(['id' => $user->getId()]);
            if (!$apprenant) {
                $this->chatbotLogger->logAction('authorization_error', [
                    'user_id' => $user->getId(),
                    'user_role' => $user->getRole(),
                    'message' => 'Tentative d\'effacement de l\'historique par un non-apprenant'
                ]);
                $this->logger->error('Utilisateur non apprenant: ' . $user->getRole());
                return $this->json(['error' => 'Seuls les apprenants peuvent effacer l\'historique du chatbot'], 403);
            }

            $this->logger->info('Apprenant trouvé: ' . $apprenant->getId());

            // Vérifier si l'apprenant a des conversations
            $conversations = $this->chatbotConversationRepository->findByApprenant($apprenant);
            $this->logger->info('Nombre de conversations trouvées: ' . count($conversations));

            // Effacer l'historique
            try {
                $this->logger->info('Tentative de suppression des conversations');
                $deletedCount = $this->chatbotService->clearConversationHistory($apprenant);
                $this->logger->info('Conversations supprimées: ' . $deletedCount);
            } catch (\Exception $innerException) {
                $this->logger->error('Erreur lors de la suppression des conversations: ' . $innerException->getMessage(), [
                    'exception' => get_class($innerException),
                    'file' => $innerException->getFile(),
                    'line' => $innerException->getLine(),
                    'trace' => $innerException->getTraceAsString()
                ]);
                throw $innerException;
            }

            // Journaliser l'action (en plus du log dans le service)
            $this->chatbotLogger->logAction('history_cleared', [
                'user_id' => $apprenant->getId(),
                'deleted_count' => $deletedCount,
                'ip' => $request->getClientIp()
            ]);

            $this->logger->info('Fin de la requête clearHistory avec succès');

            return $this->json([
                'success' => true,
                'message' => 'Historique effacé avec succès',
                'deletedCount' => $deletedCount
            ]);
        } catch (\Exception $e) {
            // Journaliser l'erreur de manière détaillée
            $this->logger->error('Erreur dans clearHistory: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->chatbotLogger->logError($e);

            return $this->json([
                'error' => 'Une erreur est survenue: ' . $e->getMessage(),
                'type' => get_class($e)
            ], 500);
        }
    }

    /**
     * Test de connexion à l'API Ollama
     */
    #[Route('/test-ollama', name: 'api_chatbot_test_ollama', methods: ['GET'])]
    public function testOllamaConnection(): JsonResponse
    {
        try {
            $result = $this->chatbotService->testConnection();

            return $this->json($result);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du test de connexion à Ollama: ' . $e->getMessage());

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du test de connexion à Ollama',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test simple pour vérifier que le contrôleur fonctionne correctement
     */
    #[Route('/test', name: 'api_chatbot_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        try {
            // Journaliser l'appel au test
            $this->logger->info('Test du contrôleur ChatbotController');

            // Récupérer l'utilisateur connecté
            $user = $this->security->getUser();
            $userId = $user ? $user->getId() : 'non authentifié';

            return $this->json([
                'success' => true,
                'message' => 'Le contrôleur ChatbotController fonctionne correctement',
                'user_id' => $userId,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'service_class' => get_class($this->chatbotService)
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du test du contrôleur: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du test du contrôleur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test de l'envoi d'un message simple (pour le débogage)
     */
    #[Route('/debug-message', name: 'api_chatbot_debug_message', methods: ['GET'])]
    public function debugMessage(): JsonResponse
    {
        try {
            // Journaliser l'appel au test
            $this->logger->info('Test de l\'envoi d\'un message simple');

            // Récupérer l'utilisateur connecté
            $user = $this->security->getUser();
            if (!$user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            // Vérifier que l'utilisateur est un apprenant
            $apprenant = $this->apprenantRepository->findOneBy(['id' => $user->getId()]);
            if (!$apprenant) {
                return $this->json(['error' => 'Seuls les apprenants peuvent utiliser le chatbot'], 403);
            }

            // Message de test simple
            $message = "Bonjour, comment ça va?";
            $this->logger->info('Message de test: ' . $message);

            // Récupérer l'historique récent des conversations (limité à 1)
            $history = $this->chatbotService->getConversationHistory($apprenant, 1);

            try {
                // Vérifier que Ollama est accessible
                $connectionTest = $this->chatbotService->testConnection();
                if (!$connectionTest['success']) {
                    $this->logger->error('Échec du test de connexion à Ollama', $connectionTest);
                    return $this->json([
                        'success' => false,
                        'message' => 'Échec de la connexion à Ollama',
                        'details' => $connectionTest
                    ], 500);
                }

                // Envoyer le message au service de chatbot
                $response = $this->chatbotService->sendMessage($message, $apprenant, $history, 'debug_test');

                return $this->json([
                    'success' => true,
                    'message' => 'Message envoyé avec succès',
                    'user_message' => $message,
                    'ai_response' => $response['content'],
                    'history_count' => count($history)
                ]);
            } catch (\Exception $innerException) {
                $this->logger->error('Erreur lors de l\'envoi du message: ' . $innerException->getMessage(), [
                    'exception' => get_class($innerException),
                    'file' => $innerException->getFile(),
                    'line' => $innerException->getLine(),
                    'trace' => $innerException->getTraceAsString()
                ]);

                return $this->json([
                    'success' => false,
                    'message' => 'Erreur lors de l\'envoi du message',
                    'error' => $innerException->getMessage(),
                    'trace' => $innerException->getTraceAsString()
                ], 500);
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du test de débogage: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du test de débogage',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Endpoint spécial pour le chatbot qui accepte les tokens expirés
     * Cette méthode permet de continuer à utiliser le chatbot même si le token JWT est expiré
     */
    #[Route('/message-extended', name: 'api_chatbot_message_extended', methods: ['POST'])]
    public function sendMessageWithExtendedAuth(Request $request): JsonResponse
    {
        try {
            $this->logger->info('🚀 [ChatbotExtended] Début de la requête sendMessageWithExtendedAuth');

            // Récupérer les données de la requête
            $content = $request->getContent();
            $this->logger->info('📝 [ChatbotExtended] Contenu de la requête: ' . $content);

            $data = json_decode($content, true);
            $this->logger->info('📝 [ChatbotExtended] Données décodées: ' . json_encode($data));

            $message = $data['message'] ?? '';
            $context = $data['context'] ?? null;
            $userId = $data['userId'] ?? null;

            $this->logger->info('📝 [ChatbotExtended] Message: ' . $message);
            $this->logger->info('📝 [ChatbotExtended] Context: ' . ($context ?? 'null'));
            $this->logger->info('📝 [ChatbotExtended] UserId: ' . ($userId ?? 'null'));

            // Vérifier si un ID utilisateur a été fourni
            if (!$userId) {
                $this->logger->error('❌ [ChatbotExtended] ID utilisateur manquant dans la requête');
                return $this->json([
                    'content' => 'Impossible d\'identifier l\'utilisateur. Veuillez vous reconnecter.',
                    'role' => 'assistant',
                    'isError' => true,
                    'authError' => true
                ], 400);
            }

            $this->logger->info('✅ [ChatbotExtended] ID utilisateur fourni: ' . $userId);

            // Rechercher l'apprenant directement par son ID
            $apprenant = $this->apprenantRepository->find($userId);
            if (!$apprenant) {
                $this->logger->error('❌ [ChatbotExtended] Apprenant non trouvé avec ID ' . $userId);

                // Vérifier si l'ID est numérique ou une chaîne
                if (is_numeric($userId)) {
                    $this->logger->info('🔍 [ChatbotExtended] Tentative de recherche avec ID numérique: ' . $userId);
                } else {
                    $this->logger->info('🔍 [ChatbotExtended] ID non numérique: ' . $userId);
                }

                // Lister tous les apprenants pour déboguer
                $allApprenants = $this->apprenantRepository->findAll();
                $this->logger->info('🔍 [ChatbotExtended] Nombre total d\'apprenants: ' . count($allApprenants));
                foreach ($allApprenants as $index => $app) {
                    if ($index < 5) { // Limiter à 5 pour éviter de surcharger les logs
                        $this->logger->info('🔍 [ChatbotExtended] Apprenant #' . $app->getId() . ': ' . $app->getEmail());
                    }
                }

                return $this->json([
                    'content' => 'Utilisateur non trouvé ou non autorisé à utiliser le chatbot.',
                    'role' => 'assistant',
                    'isError' => true,
                    'authError' => true
                ], 403);
            }

            $this->logger->info('✅ [ChatbotExtended] Apprenant trouvé: ' . $apprenant->getId() . ' (' . $apprenant->getEmail() . ')');

            // Récupérer l'historique récent des conversations
            $history = $this->chatbotService->getConversationHistory($apprenant, 5);
            $this->logger->info('✅ [ChatbotExtended] Historique récupéré', ['count' => count($history)]);

            try {
                // Vérifier que Ollama est accessible
                $this->logger->info('🔍 [ChatbotExtended] Test de connexion à Ollama...');
                $connectionTest = $this->chatbotService->testConnection();

                if (!$connectionTest['success']) {
                    $this->logger->error('❌ [ChatbotExtended] Échec du test de connexion à Ollama', $connectionTest);
                    return $this->json([
                        'content' => 'Désolé, je ne peux pas me connecter au service de chatbot en ce moment. Veuillez réessayer plus tard.',
                        'role' => 'assistant',
                        'isError' => true
                    ], 500);
                }

                $this->logger->info('✅ [ChatbotExtended] Connexion à Ollama réussie', [
                    'api_url' => $connectionTest['api_url'],
                    'models_count' => count($connectionTest['models'] ?? [])
                ]);

                // Envoyer le message au service de chatbot
                $this->logger->info('🔍 [ChatbotExtended] Envoi du message à Ollama...');
                $response = $this->chatbotService->sendMessage($message, $apprenant, $history, $context);

                $this->logger->info('✅ [ChatbotExtended] Réponse reçue de Ollama', [
                    'content_length' => strlen($response['content'] ?? ''),
                    'is_error' => isset($response['isError']) ? $response['isError'] : false
                ]);

                // Journaliser la réponse complète pour le débogage
                $this->logger->info('📝 [ChatbotExtended] Réponse complète: ' . json_encode($response));

                return $this->json($response);
            } catch (\Exception $e) {
                $this->logger->error('❌ [ChatbotExtended] Erreur lors de l\'envoi du message à Ollama', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);

                return $this->json([
                    'content' => 'Désolé, une erreur est survenue lors de la communication avec le service de chatbot. Veuillez réessayer plus tard.',
                    'role' => 'assistant',
                    'isError' => true,
                    'error_details' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            // Journaliser l'erreur
            $this->logger->error('❌ [ChatbotExtended] Exception dans sendMessageWithExtendedAuth', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($this->chatbotLogger)) {
                $this->chatbotLogger->logError($e, $message ?? null);
            }

            return $this->json([
                'error' => 'Une erreur est survenue: ' . $e->getMessage(),
                'content' => 'Désolé, une erreur est survenue lors du traitement de votre demande.',
                'role' => 'assistant',
                'isError' => true
            ], 500);
        }
    }

    /**
     * Récupère l'historique des conversations avec authentification étendue
     */
    #[Route('/history-extended', name: 'api_chatbot_history_extended', methods: ['POST'])]
    public function getHistoryWithExtendedAuth(Request $request): JsonResponse
    {
        try {
            $this->logger->info('Début de la requête getHistoryWithExtendedAuth');

            // Récupérer les données de la requête
            $data = json_decode($request->getContent(), true);
            $userId = $data['userId'] ?? null;

            // Vérifier si un ID utilisateur a été fourni
            if (!$userId) {
                $this->logger->error('ID utilisateur manquant dans la requête');
                return $this->json([
                    'error' => 'ID utilisateur requis',
                    'authError' => true
                ], 400);
            }

            // Rechercher l'apprenant directement par son ID
            $apprenant = $this->apprenantRepository->find($userId);
            if (!$apprenant) {
                $this->logger->error('Apprenant non trouvé avec ID ' . $userId);
                return $this->json([
                    'error' => 'Utilisateur non trouvé ou non autorisé',
                    'authError' => true
                ], 403);
            }

            // Récupérer les conversations
            $limit = $data['limit'] ?? 20;
            $conversations = $this->chatbotConversationRepository->findByApprenant($apprenant, $limit);

            // Formater les conversations pour le frontend
            $formattedConversations = [];
            foreach ($conversations as $conversation) {
                $formattedConversations[] = [
                    'id' => $conversation->getId(),
                    'userMessage' => [
                        'content' => $conversation->getUserMessage(),
                        'role' => 'user',
                        'time' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
                    ],
                    'aiResponse' => [
                        'content' => $conversation->getAiResponse(),
                        'role' => 'assistant',
                        'time' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
                    ],
                    'context' => $conversation->getContext(),
                    'createdAt' => $conversation->getCreatedAt()->format('Y-m-d H:i:s')
                ];
            }

            return $this->json(['conversations' => $formattedConversations]);
        } catch (\Exception $e) {
            $this->logger->error('Exception dans getHistoryWithExtendedAuth: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->json([
                'error' => 'Une erreur est survenue: ' . $e->getMessage(),
                'authError' => false
            ], 500);
        }
    }

    /**
     * Endpoint de test pour vérifier que les routes sont correctement configurées
     * Cet endpoint est accessible sans authentification
     */
    #[Route('/test-endpoint', name: 'api_chatbot_test_endpoint', methods: ['GET'])]
    public function testEndpoint(): JsonResponse
    {
        $this->logger->info('🚀 [ChatbotTest] Appel de l\'endpoint de test');

        return $this->json([
            'success' => true,
            'message' => 'L\'endpoint de test fonctionne correctement',
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'symfony_environment' => $this->params->get('kernel.environment'),
                'server_time' => date('Y-m-d H:i:s')
            ]
        ]);
    }
}
