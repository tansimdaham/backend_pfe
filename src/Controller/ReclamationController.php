<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\Reclamation;
use App\Entity\Utilisateur;
use App\Repository\ReclamationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/reclamation')]
class ReclamationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ReclamationRepository $reclamationRepository,
        private UtilisateurRepository $utilisateurRepository,
        private Security $security,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'api_reclamation_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $reclamations = $this->reclamationRepository->findAll();

            return $this->json([
                'reclamations' => $reclamations
            ], 200, [], ['groups' => 'reclamation:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/user', name: 'api_reclamation_user', methods: ['GET'])]
    public function getUserReclamations(): JsonResponse
    {
        try {
            /** @var Utilisateur $user */
            $user = $this->security->getUser();

            if (!$user) {
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            $reclamations = $this->reclamationRepository->findBy(['user' => $user]);

            return $this->json([
                'reclamations' => $reclamations
            ], 200, [], ['groups' => 'reclamation:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_reclamation_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $reclamation = $this->reclamationRepository->find($id);

            if (!$reclamation) {
                return $this->json(['error' => 'Reclamation not found'], 404);
            }

            return $this->json([
                'reclamation' => $reclamation
            ], 200, [], ['groups' => 'reclamation:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_reclamation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['subject']) || !isset($data['message'])) {
                return $this->json(['error' => 'Missing required fields'], 400);
            }

            /** @var Utilisateur $user */
            $user = $this->security->getUser();

            if (!$user) {
                return $this->json(['error' => 'User not authenticated'], 401);
            }

            $reclamation = new Reclamation();
            $reclamation->setSubject($data['subject']);
            $reclamation->setMessage($data['message']);
            $reclamation->setUser($user);
            $reclamation->setStatus('pending');
            $reclamation->setDate(new \DateTime());

            $this->entityManager->persist($reclamation);

            // Créer des notifications pour les administrateurs
            try {
                // Récupérer tous les administrateurs
                $administrateurs = $this->entityManager->getRepository(\App\Entity\Administrateur::class)->findAll();

                foreach ($administrateurs as $admin) {
                    $notification = new Notification();
                    $notification->setDescription("Nouvelle réclamation: '" . $reclamation->getSubject() . "' de " . $user->getEmail());
                    $notification->setReclamation($reclamation);
                    $notification->setUser($admin->getUtilisateur());
                    $notification->setRead(false);
                    $notification->setCreatedAt(new \DateTimeImmutable());

                    $this->entityManager->persist($notification);
                    error_log('Notification créée pour l\'administrateur ' . $admin->getId() . ' concernant la réclamation ' . $reclamation->getSubject());
                }
            } catch (\Exception $e) {
                error_log('Erreur lors de la création des notifications pour les administrateurs: ' . $e->getMessage());
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Reclamation created successfully',
                'reclamation' => $reclamation
            ], 201, [], ['groups' => 'reclamation:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/reply', name: 'api_reclamation_reply', methods: ['POST'])]
    public function reply(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['response'])) {
                return $this->json(['error' => 'Missing response field'], 400);
            }

            $reclamation = $this->reclamationRepository->find($id);

            if (!$reclamation) {
                return $this->json(['error' => 'Reclamation not found'], 404);
            }

            // Conserver l'ancienne réponse pour la compatibilité
            $reclamation->setResponse($data['response']);

            // Ajouter la nouvelle réponse au tableau des réponses
            /** @var Utilisateur $admin */
            $admin = $this->security->getUser();
            $adminName = 'Administrateur';

            if ($admin && $admin->getName()) {
                $adminName = $admin->getName();
            }

            $currentDate = new \DateTime();

            $reclamation->addResponse($data['response'], $adminName, $currentDate);
            $reclamation->setStatus('resolved');
            $reclamation->setResponseDate($currentDate);

            // Créer une notification pour l'utilisateur
            try {
                $notification = new Notification();
                $notification->setDescription("Votre réclamation '" . $reclamation->getSubject() . "' a reçu une réponse");
                $notification->setReclamation($reclamation);
                $notification->setUser($reclamation->getUser());
                $notification->setRead(false);
                $notification->setCreatedAt(new \DateTimeImmutable());

                $this->entityManager->persist($notification);
                error_log('Notification créée pour l\'utilisateur ' . $reclamation->getUser()->getId() . ' concernant la réclamation ' . $reclamation->getId());
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la notification: ' . $e->getMessage());
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Reply sent successfully',
                'reclamation' => $reclamation
            ], 200, [], ['groups' => 'reclamation:read']);
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur dans ReclamationController::reply: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());

            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
