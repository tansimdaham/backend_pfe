<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Notification;
use App\Repository\AdministrateurRepository;
use App\Repository\ApprenantRepository;
use App\Repository\FormateurRepository;
use App\Repository\EvenementRepository;
use App\Service\WebSocketNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/evenement')]
class EvenementController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvenementRepository $evenementRepository,
        private AdministrateurRepository $administrateurRepository,
        private ApprenantRepository $apprenantRepository,
        private FormateurRepository $formateurRepository,
        private Security $security,
        private SerializerInterface $serializer,
        private WebSocketNotificationService $webSocketNotificationService
    ) {}

    #[Route('/debug', name: 'api_evenement_debug', methods: ['GET'], priority: 10)]
    public function debug(): JsonResponse
    {
        try {
            // Récupérer l'utilisateur actuel
            $user = $this->security->getUser();

            if (!$user) {
                return $this->json([
                    'authenticated' => false,
                    'message' => 'Aucun utilisateur authentifié'
                ]);
            }

            // Récupérer les informations de l'utilisateur
            $userData = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'roles' => $user->getRoles(),
                'isApproved' => $user->isApproved(),
                'class' => get_class($user)
            ];

            // Vérifier si l'utilisateur est un administrateur
            $isAdmin = in_array('ROLE_ADMINISTRATEUR', $user->getRoles());

            return $this->json([
                'authenticated' => true,
                'user' => $userData,
                'isAdmin' => $isAdmin,
                'hasAdminRole' => $isAdmin,
                'server_time' => new \DateTime()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('', name: 'api_evenement_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        try {
            // Récupérer les filtres de la requête
            $dateDebut = $request->query->get('dateDebut');
            $dateFin = $request->query->get('dateFin');
            $administrateurId = $request->query->get('administrateurId');
            $categorie = $request->query->get('categorie');

            // Appliquer les filtres
            if ($dateDebut && $dateFin) {
                $start = new \DateTime($dateDebut);
                $end = new \DateTime($dateFin);
                $evenements = $this->evenementRepository->findByDateRange($start, $end);
            } elseif ($administrateurId) {
                $evenements = $this->evenementRepository->findByAdministrateur($administrateurId);
            } else {
                $evenements = $this->evenementRepository->findAll();
            }

            // Filtrer par catégorie si spécifiée
            if ($categorie && !empty($evenements)) {
                $evenements = array_filter($evenements, function($evenement) use ($categorie) {
                    return $evenement->getCategorie() === $categorie;
                });
            }

            return $this->json([
                'evenements' => $evenements
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_evenement_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $evenement = $this->evenementRepository->find($id);

            if (!$evenement) {
                return $this->json([
                    'error' => 'Événement non trouvé'
                ], 404);
            }

            return $this->json([
                'evenement' => $evenement
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_evenement_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des données
            if (!isset($data['titre']) || !isset($data['dateDebut'])) {
                return $this->json([
                    'error' => 'Missing required fields',
                    'required' => ['titre', 'dateDebut']
                ], 400);
            }

            // Créer un nouvel événement
            $evenement = new Evenement();
            $evenement->setTitre($data['titre']);
            $evenement->setDescription($data['description'] ?? null);

            // Gérer les dates
            $dateDebut = new \DateTime($data['dateDebut']);
            $evenement->setDateDebut($dateDebut);

            if (isset($data['dateFin'])) {
                $dateFin = new \DateTime($data['dateFin']);
                $evenement->setDateFin($dateFin);
            }

            $evenement->setJourneeEntiere($data['journeeEntiere'] ?? true);
            $evenement->setCategorie($data['categorie'] ?? 'evaluation');
            $evenement->setCouleur($data['couleur'] ?? '#EA4335');

            // Associer l'administrateur actuel
            $user = $this->security->getUser();
            if ($user && method_exists($user, 'getAdministrateur')) {
                $admin = $user->getAdministrateur();
                if ($admin) {
                    $evenement->addAdministrateur($admin);
                }
            }

            // Associer d'autres administrateurs si spécifiés
            if (isset($data['administrateurs']) && is_array($data['administrateurs'])) {
                foreach ($data['administrateurs'] as $adminId) {
                    $admin = $this->administrateurRepository->find($adminId);
                    if ($admin) {
                        $evenement->addAdministrateur($admin);
                    }
                }
            }

            $this->entityManager->persist($evenement);

            // Créer une notification pour chaque apprenant
            $apprenants = $this->apprenantRepository->findAll();
            $approvedApprenants = [];

            foreach ($apprenants as $apprenant) {
                // Vérifier si l'apprenant est approuvé
                if ($apprenant->isApproved()) {
                    $notification = new Notification();
                    $notification->setDescription("Nouvel événement créé : " . $evenement->getTitre());
                    $notification->setEvenement($evenement);
                    $notification->setUser($apprenant->getUtilisateur());
                    $notification->setRead(false);
                    $notification->setCreatedAt(new \DateTimeImmutable());
                    $notification->setType('evenement');

                    $this->entityManager->persist($notification);
                    $approvedApprenants[] = $apprenant;
                }
            }

            // Créer une notification pour chaque formateur
            $formateurs = $this->formateurRepository->findAll();
            $approvedFormateurs = [];

            foreach ($formateurs as $formateur) {
                // Vérifier si le formateur est approuvé
                if ($formateur->isApproved()) {
                    $notification = new Notification();
                    $notification->setDescription("Nouvel événement créé : " . $evenement->getTitre());
                    $notification->setEvenement($evenement);
                    $notification->setUser($formateur->getUtilisateur());
                    $notification->setRead(false);
                    $notification->setCreatedAt(new \DateTimeImmutable());
                    $notification->setType('evenement');

                    $this->entityManager->persist($notification);
                    $approvedFormateurs[] = $formateur;
                }
            }

            // Enregistrer les notifications dans la base de données
            $this->entityManager->flush();

            // Envoyer les notifications en temps réel via WebSocket
            foreach ($approvedApprenants as $apprenant) {
                $this->webSocketNotificationService->sendNotificationToUser(
                    $notification,
                    $apprenant->getUtilisateur()
                );
            }

            foreach ($approvedFormateurs as $formateur) {
                $this->webSocketNotificationService->sendNotificationToUser(
                    $notification,
                    $formateur->getUtilisateur()
                );
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Événement créé avec succès',
                'evenement' => $evenement
            ], 201, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_evenement_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $evenement = $this->evenementRepository->find($id);

            if (!$evenement) {
                return $this->json([
                    'error' => 'Événement non trouvé'
                ], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour les champs
            if (isset($data['titre'])) {
                $evenement->setTitre($data['titre']);
            }

            if (isset($data['description'])) {
                $evenement->setDescription($data['description']);
            }

            if (isset($data['dateDebut'])) {
                $dateDebut = new \DateTime($data['dateDebut']);
                $evenement->setDateDebut($dateDebut);
            }

            if (isset($data['dateFin'])) {
                $dateFin = new \DateTime($data['dateFin']);
                $evenement->setDateFin($dateFin);
            }

            if (isset($data['journeeEntiere'])) {
                $evenement->setJourneeEntiere($data['journeeEntiere']);
            }

            if (isset($data['categorie'])) {
                $evenement->setCategorie($data['categorie']);
            }

            if (isset($data['couleur'])) {
                $evenement->setCouleur($data['couleur']);
            }

            // Mettre à jour les administrateurs si spécifiés
            if (isset($data['administrateurs']) && is_array($data['administrateurs'])) {
                // Supprimer les associations existantes
                foreach ($evenement->getAdministrateurs() as $admin) {
                    $evenement->removeAdministrateur($admin);
                }

                // Ajouter les nouvelles associations
                foreach ($data['administrateurs'] as $adminId) {
                    $admin = $this->administrateurRepository->find($adminId);
                    if ($admin) {
                        $evenement->addAdministrateur($admin);
                    }
                }
            }

            $this->entityManager->flush();

            // Créer une notification de mise à jour pour chaque apprenant
            $apprenants = $this->apprenantRepository->findAll();
            $approvedApprenants = [];

            foreach ($apprenants as $apprenant) {
                // Vérifier si l'apprenant est approuvé
                if ($apprenant->isApproved()) {
                    $notification = new Notification();
                    $notification->setDescription("Événement mis à jour : " . $evenement->getTitre());
                    $notification->setEvenement($evenement);
                    $notification->setUser($apprenant->getUtilisateur());
                    $notification->setRead(false);
                    $notification->setCreatedAt(new \DateTimeImmutable());
                    $notification->setType('evenement');

                    $this->entityManager->persist($notification);
                    $approvedApprenants[] = $apprenant;
                }
            }

            // Créer une notification de mise à jour pour chaque formateur
            $formateurs = $this->formateurRepository->findAll();
            $approvedFormateurs = [];

            foreach ($formateurs as $formateur) {
                // Vérifier si le formateur est approuvé
                if ($formateur->isApproved()) {
                    $notification = new Notification();
                    $notification->setDescription("Événement mis à jour : " . $evenement->getTitre());
                    $notification->setEvenement($evenement);
                    $notification->setUser($formateur->getUtilisateur());
                    $notification->setRead(false);
                    $notification->setCreatedAt(new \DateTimeImmutable());
                    $notification->setType('evenement');

                    $this->entityManager->persist($notification);
                    $approvedFormateurs[] = $formateur;
                }
            }

            // Enregistrer les notifications dans la base de données
            $this->entityManager->flush();

            // Envoyer les notifications en temps réel via WebSocket
            foreach ($approvedApprenants as $apprenant) {
                $this->webSocketNotificationService->sendNotificationToUser(
                    $notification,
                    $apprenant->getUtilisateur()
                );
            }

            foreach ($approvedFormateurs as $formateur) {
                $this->webSocketNotificationService->sendNotificationToUser(
                    $notification,
                    $formateur->getUtilisateur()
                );
            }

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Événement mis à jour avec succès',
                'evenement' => $evenement
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_evenement_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $evenement = $this->evenementRepository->find($id);

            if (!$evenement) {
                return $this->json([
                    'error' => 'Événement non trouvé'
                ], 404);
            }

            // Supprimer l'événement
            $this->entityManager->remove($evenement);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Événement supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/administrateur/{id}', name: 'api_evenement_by_administrateur', methods: ['GET'], priority: 10)]
    public function getByAdministrateur(int $id): JsonResponse
    {
        try {
            $admin = $this->administrateurRepository->find($id);

            if (!$admin) {
                return $this->json([
                    'error' => 'Administrateur non trouvé'
                ], 404);
            }

            $evenements = $this->evenementRepository->findByAdministrateur($id);

            return $this->json([
                'evenements' => $evenements
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/date-range', name: 'api_evenement_by_date_range', methods: ['GET'], priority: 10)]
    public function getByDateRange(Request $request): JsonResponse
    {
        try {
            $start = $request->query->get('start');
            $end = $request->query->get('end');

            if (!$start || !$end) {
                return $this->json([
                    'error' => 'Missing required parameters',
                    'required' => ['start', 'end']
                ], 400);
            }

            $startDate = new \DateTime($start);
            $endDate = new \DateTime($end);

            $evenements = $this->evenementRepository->findByDateRange($startDate, $endDate);

            return $this->json([
                'evenements' => $evenements
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/upcoming', name: 'api_evenement_upcoming', methods: ['GET'], priority: 10)]
    public function getUpcoming(Request $request): JsonResponse
    {
        try {
            $limit = $request->query->get('limit', 5);

            $evenements = $this->evenementRepository->findUpcomingEvents($limit);

            return $this->json([
                'evenements' => $evenements
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/{id}/administrateur/{adminId}', name: 'api_evenement_add_administrateur', methods: ['POST'])]
    public function addAdministrateur(int $id, int $adminId): JsonResponse
    {
        try {
            $evenement = $this->evenementRepository->find($id);

            if (!$evenement) {
                return $this->json([
                    'error' => 'Événement non trouvé'
                ], 404);
            }

            $admin = $this->administrateurRepository->find($adminId);

            if (!$admin) {
                return $this->json([
                    'error' => 'Administrateur non trouvé'
                ], 404);
            }

            // Associer l'administrateur à l'événement
            $evenement->addAdministrateur($admin);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Administrateur associé avec succès',
                'evenement' => $evenement
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/administrateur/{adminId}', name: 'api_evenement_remove_administrateur', methods: ['DELETE'])]
    public function removeAdministrateur(int $id, int $adminId): JsonResponse
    {
        try {
            $evenement = $this->evenementRepository->find($id);

            if (!$evenement) {
                return $this->json([
                    'error' => 'Événement non trouvé'
                ], 404);
            }

            $admin = $this->administrateurRepository->find($adminId);

            if (!$admin) {
                return $this->json([
                    'error' => 'Administrateur non trouvé'
                ], 404);
            }

            // Dissocier l'administrateur de l'événement
            $evenement->removeAdministrateur($admin);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Administrateur dissocié avec succès',
                'evenement' => $evenement
            ], 200, [], ['groups' => 'evenement:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
