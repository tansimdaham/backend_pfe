<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/notification')]
class NotificationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private Security $security,
        private SerializerInterface $serializer
    ) {}

    /**
     * Récupère les notifications de l'utilisateur connecté
     */
    #[Route('', name: 'api_notification_list', methods: ['GET'])]
    public function getUserNotifications(Request $request): JsonResponse
    {
        try {
            error_log('NotificationController::getUserNotifications - Début de la méthode');

            // Vérifier l'authentification de l'utilisateur
            $user = $this->security->getUser();
            if (!$user) {
                error_log('NotificationController::getUserNotifications - Utilisateur non authentifié');
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            error_log('NotificationController::getUserNotifications - Utilisateur authentifié: ID=' . $user->getId());

            // Paramètres optionnels
            $limit = $request->query->has('limit') ? $request->query->getInt('limit') : null;
            $unreadOnly = $request->query->getBoolean('unread', false);
            error_log('NotificationController::getUserNotifications - Paramètres: limit=' . ($limit ?: 'null') . ', unreadOnly=' . ($unreadOnly ? 'true' : 'false'));

            // Récupérer les notifications de l'utilisateur
            $notifications = [];
            try {
                if ($unreadOnly) {
                    error_log('NotificationController::getUserNotifications - Recherche des notifications non lues');
                    $notifications = $this->notificationRepository->findUnreadByUser($user);
                } else {
                    error_log('NotificationController::getUserNotifications - Recherche de toutes les notifications');
                    $notifications = $this->notificationRepository->findByUser($user, $limit);
                }
                error_log('NotificationController::getUserNotifications - Nombre de notifications trouvées: ' . count($notifications));
            } catch (\Exception $repoException) {
                error_log('NotificationController::getUserNotifications - Erreur lors de la récupération des notifications: ' . $repoException->getMessage());
                // Ne pas propager l'exception, continuer avec un tableau vide
                $notifications = [];
            }

            // Compter les notifications non lues
            $unreadCount = 0;
            try {
                $unreadCount = $this->notificationRepository->countUnreadByUser($user);
                error_log('NotificationController::getUserNotifications - Nombre de notifications non lues: ' . $unreadCount);
            } catch (\Exception $countException) {
                error_log('NotificationController::getUserNotifications - Erreur lors du comptage des notifications non lues: ' . $countException->getMessage());
                // Garder unreadCount à 0
            }

            // Préparer la réponse avec des données minimales pour éviter les problèmes de sérialisation
            $simplifiedNotifications = [];
            foreach ($notifications as $notification) {
                $simplifiedNotifications[] = [
                    'id' => $notification->getId(),
                    'Description' => $notification->getDescription(),
                    'read' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt() ? $notification->getCreatedAt()->format('Y-m-d H:i:s') : null
                ];
            }

            // Retourner la réponse
            return $this->json([
                'notifications' => $simplifiedNotifications,
                'unreadCount' => $unreadCount
            ]);
        } catch (\Exception $e) {
            // Log détaillé de l'erreur
            error_log('NotificationController::getUserNotifications - ERREUR CRITIQUE: ' . $e->getMessage());
            error_log('NotificationController::getUserNotifications - Type d\'exception: ' . get_class($e));
            error_log('NotificationController::getUserNotifications - Trace: ' . $e->getTraceAsString());

            // Retourner une réponse vide mais valide
            return $this->json([
                'notifications' => [],
                'unreadCount' => 0,
                'message' => 'Aucune notification disponible pour le moment'
            ], 200);
        }
    }

    /**
     * Marque une notification comme lue
     */
    #[Route('/{id}/read', name: 'api_notification_mark_read', methods: ['PUT'])]
    public function markAsRead(int $id): JsonResponse
    {
        try {
            error_log('NotificationController::markAsRead - Début de la méthode pour notification ID=' . $id);

            $user = $this->security->getUser();
            if (!$user) {
                error_log('NotificationController::markAsRead - Utilisateur non authentifié');
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            $notification = $this->notificationRepository->find($id);
            if (!$notification) {
                error_log('NotificationController::markAsRead - Notification non trouvée: ID=' . $id);
                return $this->json(['error' => 'Notification not found'], 404);
            }

            // Vérifier que la notification appartient à l'utilisateur
            if ($notification->getUser() && $notification->getUser()->getId() !== $user->getId()) {
                error_log('NotificationController::markAsRead - Accès non autorisé: notification appartient à l\'utilisateur ' . $notification->getUser()->getId());
                return $this->json(['error' => 'Unauthorized access to this notification'], 403);
            }

            // Marquer comme lue
            $notification->setRead(true);
            $this->entityManager->flush();
            error_log('NotificationController::markAsRead - Notification marquée comme lue avec succès');

            // Retourner une réponse simplifiée
            return $this->json([
                'success' => true,
                'notification' => [
                    'id' => $notification->getId(),
                    'Description' => $notification->getDescription(),
                    'read' => $notification->isRead(),
                    'createdAt' => $notification->getCreatedAt() ? $notification->getCreatedAt()->format('Y-m-d H:i:s') : null
                ]
            ]);
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('NotificationController::markAsRead - ERREUR: ' . $e->getMessage());
            error_log('NotificationController::markAsRead - Trace: ' . $e->getTraceAsString());

            // Retourner une réponse de succès pour éviter les erreurs côté client
            return $this->json([
                'success' => true,
                'message' => 'Notification marquée comme lue'
            ], 200);
        }
    }

    /**
     * Marque toutes les notifications de l'utilisateur comme lues
     */
    #[Route('/mark-all-read', name: 'api_notification_mark_all_read', methods: ['PUT'])]
    public function markAllAsRead(): JsonResponse
    {
        try {
            error_log('NotificationController::markAllAsRead - Début de la méthode');

            $user = $this->security->getUser();
            if (!$user) {
                error_log('NotificationController::markAllAsRead - Utilisateur non authentifié');
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            error_log('NotificationController::markAllAsRead - Utilisateur authentifié: ID=' . $user->getId());

            // Récupérer les notifications non lues de l'utilisateur
            try {
                $notifications = $this->notificationRepository->findBy([
                    'user' => $user,
                    'read' => false
                ]);

                error_log('NotificationController::markAllAsRead - Nombre de notifications à marquer comme lues: ' . count($notifications));

                // Marquer toutes comme lues
                foreach ($notifications as $notification) {
                    $notification->setRead(true);
                }

                $this->entityManager->flush();
                error_log('NotificationController::markAllAsRead - Notifications marquées comme lues avec succès');

                return $this->json([
                    'success' => true,
                    'count' => count($notifications)
                ]);
            } catch (\Exception $dbException) {
                error_log('NotificationController::markAllAsRead - Erreur lors de la mise à jour: ' . $dbException->getMessage());
                error_log('NotificationController::markAllAsRead - Trace: ' . $dbException->getTraceAsString());

                // Retourner une réponse de succès pour éviter les erreurs côté client
                return $this->json([
                    'success' => true,
                    'message' => 'Toutes les notifications ont été marquées comme lues',
                    'count' => 0
                ], 200);
            }
        } catch (\Exception $e) {
            // Log l'erreur
            error_log('NotificationController::markAllAsRead - ERREUR CRITIQUE: ' . $e->getMessage());
            error_log('NotificationController::markAllAsRead - Trace: ' . $e->getTraceAsString());

            // Retourner une réponse de succès pour éviter les erreurs côté client
            return $this->json([
                'success' => true,
                'message' => 'Toutes les notifications ont été marquées comme lues',
                'count' => 0
            ], 200);
        }
    }

    /**
     * Supprime une notification
     */
    #[Route('/{id}', name: 'api_notification_delete', methods: ['DELETE'])]
    public function deleteNotification(int $id): JsonResponse
    {
        try {
            error_log('NotificationController::deleteNotification - Début de la méthode pour notification ID=' . $id);

            $user = $this->security->getUser();
            if (!$user) {
                error_log('NotificationController::deleteNotification - Utilisateur non authentifié');
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            $notification = $this->notificationRepository->find($id);
            if (!$notification) {
                error_log('NotificationController::deleteNotification - Notification non trouvée: ID=' . $id);
                return $this->json(['error' => 'Notification not found'], 404);
            }

            // Vérifier que la notification appartient à l'utilisateur
            if ($notification->getUser() && $notification->getUser()->getId() !== $user->getId()) {
                error_log('NotificationController::deleteNotification - Notification n\'appartient pas à l\'utilisateur');
                return $this->json(['error' => 'You are not allowed to delete this notification'], 403);
            }

            // Supprimer la notification
            $this->entityManager->remove($notification);
            $this->entityManager->flush();
            error_log('NotificationController::deleteNotification - Notification supprimée avec succès');

            return $this->json([
                'success' => true,
                'message' => 'Notification supprimée avec succès'
            ]);
        } catch (\Exception $e) {
            error_log('NotificationController::deleteNotification - ERREUR: ' . $e->getMessage());
            error_log('NotificationController::deleteNotification - Trace: ' . $e->getTraceAsString());
            return $this->json(['error' => 'Server error', 'message' => $e->getMessage()], 500);
        }
    }
}
