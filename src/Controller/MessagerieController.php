<?php

namespace App\Controller;

use App\Entity\Messagerie;
use App\Entity\Formateur;
use App\Entity\Apprenant;
use App\Entity\Notification;
use App\Repository\MessagerieRepository;
use App\Repository\FormateurRepository;
use App\Repository\ApprenantRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/messagerie')]
class MessagerieController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessagerieRepository $messagerieRepository,
        private FormateurRepository $formateurRepository,
        private ApprenantRepository $apprenantRepository,
        private UtilisateurRepository $utilisateurRepository,
        private Security $security,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    /**
     * Récupère tous les messages entre un formateur et un apprenant
     */
    #[Route('/formateur/{formateurId}/apprenant/{apprenantId}', name: 'api_messagerie_get_conversation', methods: ['GET'])]
    public function getConversation(int $formateurId, int $apprenantId): JsonResponse
    {
        try {
            error_log('Starting getConversation for formateur ' . $formateurId . ' and apprenant ' . $apprenantId);

            $formateur = $this->formateurRepository->find($formateurId);
            $apprenant = $this->apprenantRepository->find($apprenantId);

            if (!$formateur) {
                error_log('Formateur not found with ID: ' . $formateurId);
                return $this->json(['error' => 'Formateur non trouvé (ID: ' . $formateurId . ')'], Response::HTTP_NOT_FOUND);
            }

            if (!$apprenant) {
                error_log('Apprenant not found with ID: ' . $apprenantId);
                return $this->json(['error' => 'Apprenant non trouvé (ID: ' . $apprenantId . ')'], Response::HTTP_NOT_FOUND);
            }

            error_log('Found formateur and apprenant, fetching conversation');
            $messages = $this->messagerieRepository->findConversation($formateurId, $apprenantId);

            // Log for debugging
            error_log('Conversation between formateur ' . $formateurId . ' and apprenant ' . $apprenantId . ': ' . count($messages) . ' messages found');

            // If no messages, return an empty array instead of null
            if (empty($messages)) {
                error_log('No messages found, returning empty array');
                return $this->json([
                    'messages' => []
                ], Response::HTTP_OK, [], ['groups' => 'messagerie:read']);
            }

            return $this->json([
                'messages' => $messages
            ], Response::HTTP_OK, [], ['groups' => 'messagerie:read']);
        } catch (\Exception $e) {
            error_log('Error in getConversation: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère toutes les conversations d'un formateur
     */
    #[Route('/formateur/{formateurId}/conversations', name: 'api_messagerie_formateur_conversations', methods: ['GET'])]
    public function getFormateurConversations(int $formateurId): JsonResponse
    {
        try {
            $formateur = $this->formateurRepository->find($formateurId);

            if (!$formateur) {
                return $this->json(['error' => 'Formateur non trouvé (ID: ' . $formateurId . ')'], Response::HTTP_NOT_FOUND);
            }

            $conversations = $this->messagerieRepository->findFormateurConversations($formateurId);

            // Log for debugging
            error_log('Formateur conversations (ID: ' . $formateurId . '): ' . count($conversations) . ' conversations found');

            return $this->json([
                'conversations' => $conversations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            error_log('Error in getFormateurConversations: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère toutes les conversations d'un apprenant
     */
    #[Route('/apprenant/{apprenantId}/conversations', name: 'api_messagerie_apprenant_conversations', methods: ['GET'])]
    public function getApprenantConversations(int $apprenantId): JsonResponse
    {
        try {
            $apprenant = $this->apprenantRepository->find($apprenantId);

            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant non trouvé (ID: ' . $apprenantId . ')'], Response::HTTP_NOT_FOUND);
            }

            $conversations = $this->messagerieRepository->findApprenantConversations($apprenantId);

            // Log for debugging
            error_log('Apprenant conversations (ID: ' . $apprenantId . '): ' . count($conversations) . ' conversations found');

            return $this->json([
                'conversations' => $conversations
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            error_log('Error in getApprenantConversations: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Envoie un message d'un formateur à un apprenant
     */
    #[Route('/formateur/{formateurId}/apprenant/{apprenantId}/envoyer', name: 'api_messagerie_formateur_envoyer', methods: ['POST'])]
    public function formateurEnvoyerMessage(Request $request, int $formateurId, int $apprenantId): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['message']) || empty($data['message'])) {
                return $this->json(['error' => 'Le message ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }

            $formateur = $this->formateurRepository->find($formateurId);
            $apprenant = $this->apprenantRepository->find($apprenantId);

            if (!$formateur) {
                return $this->json(['error' => 'Formateur non trouvé (ID: ' . $formateurId . ')'], Response::HTTP_NOT_FOUND);
            }

            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant non trouvé (ID: ' . $apprenantId . ')'], Response::HTTP_NOT_FOUND);
            }

            $message = new Messagerie();
            $message->setMessage($data['message']);
            $message->setFormateur($formateur);
            $message->setApprenant($apprenant);
            $message->setDate(new \DateTime());
            $message->setLu(false);
            $message->setSentByFormateur(true); // Message envoyé par le formateur

            $this->entityManager->persist($message);

            // Créer une notification pour l'apprenant
            try {
                $notification = new Notification();
                $notification->setDescription("Nouveau message de " . $formateur->getName());
                $notification->setMessagerie($message);
                $notification->setUser($apprenant->getUtilisateur());
                $notification->setRead(false);
                $notification->setCreatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($notification);
                error_log('Notification créée pour l\'apprenant ' . $apprenantId . ' concernant un message du formateur ' . $formateurId);
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }

            $this->entityManager->flush();

            // Log for debugging
            error_log('Message sent from formateur ' . $formateurId . ' to apprenant ' . $apprenantId . ': ' . $data['message']);

            return $this->json([
                'message' => 'Message envoyé avec succès',
                'data' => $message
            ], Response::HTTP_CREATED, [], ['groups' => 'messagerie:read']);
        } catch (\Exception $e) {
            error_log('Error in formateurEnvoyerMessage: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Envoie un message d'un apprenant à un formateur
     */
    #[Route('/apprenant/{apprenantId}/formateur/{formateurId}/envoyer', name: 'api_messagerie_apprenant_envoyer', methods: ['POST'])]
    public function apprenantEnvoyerMessage(Request $request, int $apprenantId, int $formateurId): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['message']) || empty($data['message'])) {
                return $this->json(['error' => 'Le message ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
            }

            $apprenant = $this->apprenantRepository->find($apprenantId);
            $formateur = $this->formateurRepository->find($formateurId);

            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant non trouvé (ID: ' . $apprenantId . ')'], Response::HTTP_NOT_FOUND);
            }

            if (!$formateur) {
                return $this->json(['error' => 'Formateur non trouvé (ID: ' . $formateurId . ')'], Response::HTTP_NOT_FOUND);
            }

            $message = new Messagerie();
            $message->setMessage($data['message']);
            $message->setApprenant($apprenant);
            $message->setFormateur($formateur);
            $message->setDate(new \DateTime());
            $message->setLu(false);
            $message->setSentByFormateur(false); // Message envoyé par l'apprenant

            $this->entityManager->persist($message);

            // Créer une notification pour le formateur
            try {
                $notification = new Notification();
                $notification->setDescription("Nouveau message de " . $apprenant->getName());
                $notification->setMessagerie($message);
                $notification->setUser($formateur->getUtilisateur());
                $notification->setRead(false);
                $notification->setCreatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($notification);
                error_log('Notification créée pour le formateur ' . $formateurId . ' concernant un message de l\'apprenant ' . $apprenantId);
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }

            $this->entityManager->flush();

            // Log for debugging
            error_log('Message sent from apprenant ' . $apprenantId . ' to formateur ' . $formateurId . ': ' . $data['message']);

            return $this->json([
                'message' => 'Message envoyé avec succès',
                'data' => $message
            ], Response::HTTP_CREATED, [], ['groups' => 'messagerie:read']);
        } catch (\Exception $e) {
            error_log('Error in apprenantEnvoyerMessage: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Marque un message comme lu
     */
    #[Route('/{id}/marquer-lu', name: 'api_messagerie_marquer_lu', methods: ['PUT'])]
    public function marquerLu(int $id): JsonResponse
    {
        try {
            $message = $this->messagerieRepository->find($id);

            if (!$message) {
                return $this->json(['error' => 'Message non trouvé'], Response::HTTP_NOT_FOUND);
            }

            $message->setLu(true);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Message marqué comme lu'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les formateurs disponibles pour un apprenant
     */
    #[Route('/apprenant/{apprenantId}/formateurs', name: 'api_messagerie_apprenant_formateurs', methods: ['GET'])]
    public function getFormateursForApprenant(int $apprenantId): JsonResponse
    {
        try {
            error_log('Starting getFormateursForApprenant for apprenant ' . $apprenantId);

            $apprenant = $this->apprenantRepository->find($apprenantId);

            if (!$apprenant) {
                error_log('Apprenant not found with ID: ' . $apprenantId);
                return $this->json(['error' => 'Apprenant non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Récupérer tous les formateurs approuvés
            $formateurs = $this->utilisateurRepository->createQueryBuilder('u')
                ->select('u.id, u.name, u.email, u.profileImage')
                ->where('u.isApproved = :isApproved')
                ->andWhere('u.role = :role')
                ->setParameter('isApproved', true)
                ->setParameter('role', 'formateur')
                ->orderBy('u.name', 'ASC')
                ->getQuery()
                ->getResult();

            error_log('Found ' . count($formateurs) . ' formateurs for apprenant ' . $apprenantId);

            // If no formateurs, return an empty array instead of null
            if (empty($formateurs)) {
                error_log('No formateurs found, returning empty array');
                return $this->json([
                    'formateurs' => []
                ], Response::HTTP_OK);
            }

            return $this->json([
                'formateurs' => $formateurs
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            error_log('Error in getFormateursForApprenant: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les apprenants disponibles pour un formateur
     */
    #[Route('/formateur/{formateurId}/apprenants', name: 'api_messagerie_formateur_apprenants', methods: ['GET'])]
    public function getApprenantsForFormateur(int $formateurId): JsonResponse
    {
        try {
            error_log('Starting getApprenantsForFormateur for formateur ' . $formateurId);

            $formateur = $this->formateurRepository->find($formateurId);

            if (!$formateur) {
                error_log('Formateur not found with ID: ' . $formateurId);
                return $this->json(['error' => 'Formateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            // Récupérer tous les apprenants approuvés
            $apprenants = $this->utilisateurRepository->createQueryBuilder('u')
                ->select('u.id, u.name, u.email, u.profileImage')
                ->where('u.isApproved = :isApproved')
                ->andWhere('u.role = :role')
                ->setParameter('isApproved', true)
                ->setParameter('role', 'apprenant')
                ->orderBy('u.name', 'ASC')
                ->getQuery()
                ->getResult();

            error_log('Found ' . count($apprenants) . ' apprenants for formateur ' . $formateurId);

            // If no apprenants, return an empty array instead of null
            if (empty($apprenants)) {
                error_log('No apprenants found, returning empty array');
                return $this->json([
                    'apprenants' => []
                ], Response::HTTP_OK);
            }

            return $this->json([
                'apprenants' => $apprenants
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            error_log('Error in getApprenantsForFormateur: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
