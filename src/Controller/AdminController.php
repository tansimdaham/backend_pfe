<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Administrateur;
use App\Entity\Apprenant;
use App\Entity\Formateur;
use App\Entity\Cours;
use App\Repository\UtilisateurRepository;
use App\Repository\ApprenantRepository;
use App\Repository\CoursRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
class AdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UtilisateurRepository $utilisateurRepository;
    private ApprenantRepository $apprenantRepository;
    private CoursRepository $coursRepository;
    private EmailService $emailService;

    public function __construct(
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository,
        ApprenantRepository $apprenantRepository,
        CoursRepository $coursRepository,
        EmailService $emailService
    ) {
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->apprenantRepository = $apprenantRepository;
        $this->coursRepository = $coursRepository;
        $this->emailService = $emailService;
    }

    #[Route('/users', name: 'api_admin_users', methods: ['GET', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function getApprovedUsers(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            // Récupérer tous les utilisateurs approuvés avec une requête optimisée
            $approvedUsers = $this->utilisateurRepository->createQueryBuilder('u')
                ->select('u.id, u.name, u.email, u.phone, u.role, u.profileImage')
                ->where('u.isApproved = :isApproved')
                ->setParameter('isApproved', true)
                ->orderBy('u.id', 'DESC')
                ->getQuery()
                ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

            return $this->json(['users' => $approvedUsers]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/pending', name: 'api_admin_users_pending', methods: ['GET', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function getPendingUsers(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            // Log de début de requête
            error_log('Début de la récupération des utilisateurs en attente');

            // Récupérer tous les utilisateurs non approuvés avec une requête optimisée
            // Utilisation de createQueryBuilder pour optimiser la requête en sélectionnant uniquement les champs nécessaires
            $pendingUsers = $this->utilisateurRepository->createQueryBuilder('u')
                ->select('u.id, u.name, u.email, u.phone, u.role, u.profileImage')
                ->where('u.isApproved = :isApproved')
                ->setParameter('isApproved', false)
                ->orderBy('u.id', 'DESC') // Trier par ID décroissant pour avoir les plus récents en premier
                ->getQuery()
                ->getResult(Query::HYDRATE_ARRAY); // Utiliser HYDRATE_ARRAY pour éviter de charger les entités complètes

            error_log('Nombre d\'utilisateurs en attente trouvés: ' . count($pendingUsers));

            // Comme nous utilisons HYDRATE_ARRAY, les résultats sont déjà au format tableau
            // Nous pouvons donc les utiliser directement
            $usersData = $pendingUsers;

            error_log('Fin de la récupération des utilisateurs en attente');
            return $this->json(['users' => $usersData]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la récupération des utilisateurs en attente: ' . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/approve/{id}', name: 'api_admin_user_approve', methods: ['POST', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function approveUser(Request $request, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            error_log('Début de l\'approbation de l\'utilisateur ' . $id);

            // Récupérer l'utilisateur à approuver
            $user = $this->utilisateurRepository->find($id);
            if (!$user) {
                error_log('Utilisateur ' . $id . ' non trouvé');
                return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            error_log('Utilisateur trouvé: ' . $user->getName() . ' (' . $user->getEmail() . ')');

            // Vérifier si l'utilisateur est déjà approuvé
            if ($user->isApproved()) {
                error_log('L\'utilisateur ' . $id . ' est déjà approuvé');
                return $this->json([
                    'message' => 'Cet utilisateur est déjà approuvé',
                    'user' => [
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'email' => $user->getEmail(),
                        'role' => $user->getRole()
                    ]
                ]);
            }

            // Approuver l'utilisateur
            $user->setIsApproved(true);
            $this->entityManager->flush();
            error_log('Utilisateur ' . $id . ' approuvé avec succès');

            // Envoyer un email de confirmation à l'utilisateur
            $emailSent = false;
            $emailError = null;
            try {
                $emailSent = $this->emailService->sendApprovalEmail($user);
                error_log('=================================================');
                error_log('✅ EMAIL D\'APPROBATION ENVOYÉ AVEC SUCCÈS');
                error_log('✅ Destinataire: ' . $user->getEmail());
                error_log('✅ Date: ' . date('Y-m-d H:i:s'));
                error_log('=================================================');
            } catch (\Exception $emailError) {
                error_log('=================================================');
                error_log('❌ ÉCHEC DE L\'ENVOI DE L\'EMAIL D\'APPROBATION');
                error_log('❌ Destinataire: ' . $user->getEmail());
                error_log('❌ Date: ' . date('Y-m-d H:i:s'));
                error_log('❌ Erreur: ' . $emailError->getMessage());

                // Vérifier si c'est dû à la configuration null://null
                if (strpos($emailError->getMessage(), 'null://null') !== false) {
                    error_log('ℹ️ INFORMATION: La configuration MAILER_DSN est définie sur "null://null".');
                    error_log('ℹ️ Les emails ne sont pas réellement envoyés. Modifiez le fichier .env pour configurer un transport d\'email réel.');
                }
                error_log('=================================================');
                // On continue même si l'envoi d'email échoue
            }

            return $this->json([
                'message' => 'Utilisateur approuvé avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()
                ],
                'emailSent' => $emailSent,
                'emailError' => $emailError ? $emailError->getMessage() : null
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'approbation de l\'utilisateur ' . $id . ': ' . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/reject/{id}', name: 'api_admin_user_reject', methods: ['POST', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function rejectUser(Request $request, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            error_log('Début du rejet de l\'utilisateur ' . $id);

            // Récupérer l'utilisateur à rejeter
            $user = $this->utilisateurRepository->find($id);
            if (!$user) {
                error_log('Utilisateur ' . $id . ' non trouvé');
                return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
            }

            error_log('Utilisateur trouvé: ' . $user->getName() . ' (' . $user->getEmail() . ')');

            // Récupérer la raison du rejet
            $content = $request->getContent();
            error_log('Contenu de la requête: ' . $content);

            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Erreur de décodage JSON: ' . json_last_error_msg());
                return $this->json([
                    'message' => 'Format de requête invalide: ' . json_last_error_msg()
                ], Response::HTTP_BAD_REQUEST);
            }

            $reason = $data['reason'] ?? null;
            error_log('Raison du rejet: ' . ($reason ?: 'Non spécifiée'));

            // Envoyer un email de rejet à l'utilisateur avant de le supprimer
            $emailSent = false;
            $emailError = null;
            try {
                $emailSent = $this->emailService->sendRejectionEmail($user, $reason);
                error_log('=================================================');
                error_log('✅ EMAIL DE REJET ENVOYÉ AVEC SUCCÈS');
                error_log('✅ Destinataire: ' . $user->getEmail());
                error_log('✅ Raison du rejet: ' . ($reason ?: 'Non spécifiée'));
                error_log('✅ Date: ' . date('Y-m-d H:i:s'));
                error_log('=================================================');
            } catch (\Exception $emailError) {
                error_log('=================================================');
                error_log('❌ ÉCHEC DE L\'ENVOI DE L\'EMAIL DE REJET');
                error_log('❌ Destinataire: ' . $user->getEmail());
                error_log('❌ Date: ' . date('Y-m-d H:i:s'));
                error_log('❌ Erreur: ' . $emailError->getMessage());

                // Vérifier si c'est dû à la configuration null://null
                if (strpos($emailError->getMessage(), 'null://null') !== false) {
                    error_log('ℹ️ INFORMATION: La configuration MAILER_DSN est définie sur "null://null".');
                    error_log('ℹ️ Les emails ne sont pas réellement envoyés. Modifiez le fichier .env pour configurer un transport d\'email réel.');
                }
                error_log('=================================================');
                // On continue même si l'envoi d'email échoue
            }

            // Supprimer l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            error_log('Utilisateur ' . $id . ' rejeté et supprimé avec succès');

            return $this->json([
                'message' => 'Utilisateur rejeté avec succès',
                'emailSent' => $emailSent,
                'emailError' => $emailError ? $emailError->getMessage() : null
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors du rejet de l\'utilisateur ' . $id . ': ' . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/add', name: 'api_admin_user_add', methods: ['POST', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function addUser(Request $request, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier les champs obligatoires
            if (!isset($data['name']) || !isset($data['email']) || !isset($data['role']) || !isset($data['password'])) {
                return $this->json([
                    'message' => 'Champs obligatoires manquants'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'email existe déjà
            $existingUser = $this->utilisateurRepository->findByEmail($data['email']);
            if ($existingUser) {
                return $this->json([
                    'message' => 'Cet email est déjà utilisé'
                ], Response::HTTP_CONFLICT);
            }

            // Vérifier si le nom existe déjà
            $existingUserWithSameName = $this->utilisateurRepository->findByName($data['name']);
            if ($existingUserWithSameName) {
                return $this->json([
                    'message' => 'Ce nom d\'utilisateur est déjà utilisé'
                ], Response::HTTP_CONFLICT);
            }

            // Créer l'utilisateur selon son rôle
            $user = match ($data['role']) {
                'administrateur' => new \App\Entity\Administrateur(),
                'formateur' => new \App\Entity\Formateur(),
                'apprenant' => new \App\Entity\Apprenant(),
                default => throw new \InvalidArgumentException('Rôle invalide')
            };

            // Définir les propriétés de l'utilisateur
            $user->setName($data['name']);
            $user->setEmail($data['email']);
            $user->setPhone($data['phone'] ?? 0);
            $user->setRole($data['role']);
            $user->setIsApproved(true); // L'utilisateur est automatiquement approuvé

            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Persister l'utilisateur
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Utilisateur créé avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'phone' => $user->getPhone()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/users/edit/{id}', name: 'api_admin_user_edit', methods: ['PUT', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function editUser(Request $request, int $id, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            error_log('Début de la modification de l\'utilisateur ' . $id);

            // Récupérer l'utilisateur à modifier
            $user = $this->utilisateurRepository->find($id);
            if (!$user) {
                error_log('Utilisateur ' . $id . ' non trouvé');
                return $this->json([
                    'message' => 'Utilisateur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            error_log('Utilisateur trouvé: ' . $user->getName() . ' (' . $user->getEmail() . '), ID: ' . $user->getId());

            $data = json_decode($request->getContent(), true);
            error_log('Données reçues: ' . json_encode($data));

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('Erreur JSON: ' . json_last_error_msg());
                return $this->json([
                    'message' => 'JSON invalide: ' . json_last_error_msg()
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si l'email existe déjà pour un autre utilisateur
            if (isset($data['email'])) {
                error_log('Email reçu: ' . $data['email'] . ', Email actuel: ' . $user->getEmail());

                // Si l'email a changé, vérifier s'il est déjà utilisé
                if ($data['email'] !== $user->getEmail()) {
                    error_log('L\'email a changé, vérification si déjà utilisé');
                    $existingUser = $this->utilisateurRepository->findByEmail($data['email']);

                    if ($existingUser) {
                        error_log('Email trouvé pour utilisateur ID: ' . $existingUser->getId() . ', utilisateur actuel ID: ' . $user->getId());

                        if ($existingUser->getId() !== $user->getId()) {
                            error_log('Conflit: email déjà utilisé par un autre utilisateur');
                            return $this->json([
                                'message' => 'Cet email est déjà utilisé par un autre utilisateur'
                            ], Response::HTTP_CONFLICT);
                        } else {
                            error_log('L\'email appartient au même utilisateur, pas de conflit');
                        }
                    }

                    error_log('Mise à jour de l\'email: ' . $data['email']);
                    $user->setEmail($data['email']);
                } else {
                    error_log('L\'email n\'a pas changé, aucune action nécessaire');
                }
            }

            // Vérifier si le nom existe déjà pour un autre utilisateur
            if (isset($data['name'])) {
                error_log('Nom reçu: ' . $data['name'] . ', Nom actuel: ' . $user->getName());

                if ($data['name'] !== $user->getName()) {
                    error_log('Le nom a changé, vérification si déjà utilisé');
                    $existingUserWithSameName = $this->utilisateurRepository->findByName($data['name']);

                    if ($existingUserWithSameName) {
                        error_log('Nom trouvé pour utilisateur ID: ' . $existingUserWithSameName->getId() . ', utilisateur actuel ID: ' . $user->getId());

                        if ($existingUserWithSameName->getId() !== $user->getId()) {
                            error_log('Conflit: nom déjà utilisé par un autre utilisateur');
                            return $this->json([
                                'message' => 'Ce nom d\'utilisateur est déjà utilisé par un autre utilisateur'
                            ], Response::HTTP_CONFLICT);
                        } else {
                            error_log('Le nom appartient au même utilisateur, pas de conflit');
                        }
                    }

                    error_log('Mise à jour du nom: ' . $data['name']);
                    $user->setName($data['name']);
                } else {
                    error_log('Le nom n\'a pas changé, aucune action nécessaire');
                }
            }

            // Mettre à jour les autres propriétés
            if (isset($data['phone'])) {
                $user->setPhone($data['phone']);
            }

            if (isset($data['role']) && $data['role'] !== $user->getRole()) {
                error_log('Changement de rôle détecté: ' . $user->getRole() . ' -> ' . $data['role']);

                // Créer un nouvel utilisateur avec le nouveau rôle
                $newUser = match ($data['role']) {
                    'administrateur' => new \App\Entity\Administrateur(),
                    'formateur' => new \App\Entity\Formateur(),
                    'apprenant' => new \App\Entity\Apprenant(),
                    default => throw new \InvalidArgumentException('Rôle invalide')
                };

                // Stocker les informations de l'utilisateur actuel
                $oldId = $user->getId();
                $userName = $user->getName();
                $userEmail = $user->getEmail();
                $userPhone = $user->getPhone();
                $userPassword = $user->getPassword();
                $userProfileImage = $user->getProfileImage();

                error_log('Informations utilisateur sauvegardées: ID=' . $oldId . ', Email=' . $userEmail);

                // Supprimer d'abord l'ancien utilisateur pour libérer l'email
                $this->entityManager->remove($user);
                $this->entityManager->flush();
                error_log('Ancien utilisateur supprimé');

                // Maintenant configurer le nouvel utilisateur
                $newUser->setName($userName);
                $newUser->setEmail($userEmail);
                $newUser->setPhone($userPhone);
                $newUser->setPassword($userPassword);
                $newUser->setRole($data['role']);
                $newUser->setIsApproved(true);
                $newUser->setProfileImage($userProfileImage);

                // Persister le nouvel utilisateur
                $this->entityManager->persist($newUser);
                $this->entityManager->flush();
                error_log('Nouvel utilisateur créé avec ID: ' . $newUser->getId());

                // Mettre à jour la référence de l'utilisateur pour la suite du traitement
                $user = $newUser;
            }

            // Mettre à jour le mot de passe si fourni
            if (isset($data['password']) && !empty($data['password'])) {
                $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            }

            // Ces propriétés sont déjà mises à jour plus haut dans le code
            // Nous n'avons pas besoin de les mettre à jour à nouveau ici

            // Persister les modifications
            error_log('Persistance des modifications pour l\'utilisateur ID: ' . $user->getId());
            $this->entityManager->flush();
            error_log('Modifications persistées avec succès');

            $responseData = [
                'message' => 'Utilisateur modifié avec succès',
                'user' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'phone' => $user->getPhone(),
                    'profileImage' => $user->getProfileImage()
                ]
            ];

            // Si le rôle a été changé, indiquer que l'ID a changé
            if (isset($newUser)) {
                $responseData['roleChanged'] = true;
                $responseData['oldId'] = $id;
                error_log('Rôle changé, nouvel ID: ' . $user->getId() . ', ancien ID: ' . $id);
            }

            error_log('Modification de l\'utilisateur terminée avec succès');
            return $this->json($responseData);
        } catch (\Exception $e) {
            error_log('Erreur lors de la modification de l\'utilisateur: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            // Vérifier si c'est une erreur de contrainte d'unicité
            if (strpos($e->getMessage(), 'UNIQ_1D1C63B3E7927C74') !== false) {
                return $this->json([
                    'message' => 'Erreur: L\'email est déjà utilisé par un autre utilisateur. Veuillez réessayer avec un email différent.'
                ], Response::HTTP_CONFLICT);
            } else if (strpos($e->getMessage(), 'UNIQ_1D1C63B35E237E06') !== false) {
                return $this->json([
                    'message' => 'Erreur: Le nom d\'utilisateur est déjà utilisé par un autre utilisateur. Veuillez réessayer avec un nom différent.'
                ], Response::HTTP_CONFLICT);
            } else {
                return $this->json([
                    'message' => 'Erreur serveur: ' . $e->getMessage()
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    #[Route('/users/delete/{id}', name: 'api_admin_user_delete', methods: ['DELETE', 'OPTIONS'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function deleteUser(Request $request, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            // Récupérer l'utilisateur à supprimer
            $user = $this->utilisateurRepository->find($id);
            if (!$user) {
                return $this->json([
                    'message' => 'Utilisateur non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Supprimer l'utilisateur
            $this->entityManager->remove($user);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Utilisateur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            error_log('Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants', name: 'api_admin_apprenants', methods: ['GET'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function getApprenants(): JsonResponse
    {
        try {
            // Récupérer tous les apprenants approuvés
            $apprenants = $this->utilisateurRepository->createQueryBuilder('u')
                ->select('u.id, u.name, u.email, u.phone, u.profileImage')
                ->where('u.isApproved = :isApproved')
                ->andWhere('u.role = :role')
                ->setParameter('isApproved', true)
                ->setParameter('role', 'apprenant')
                ->orderBy('u.name', 'ASC')
                ->getQuery()
                ->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);

            return $this->json(['apprenants' => $apprenants]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{id}/cours', name: 'api_admin_apprenant_cours', methods: ['GET'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function getApprenantCours(int $id): JsonResponse
    {
        try {
            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($id);
            if (!$apprenant) {
                return $this->json([
                    'message' => 'Apprenant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer les cours de l'apprenant
            $cours = $apprenant->getCours()->toArray();

            return $this->json([
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName(),
                    'email' => $apprenant->getEmail()
                ],
                'cours' => $cours
            ], Response::HTTP_OK, [], ['groups' => 'cours:read']);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{apprenantId}/cours/{coursId}', name: 'api_admin_apprenant_assign_cours', methods: ['POST'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function assignCourseToApprenant(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                return $this->json([
                    'message' => 'Apprenant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer le cours
            $cours = $this->coursRepository->find($coursId);
            if (!$cours) {
                return $this->json([
                    'message' => 'Cours non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si l'apprenant est déjà inscrit à ce cours
            if ($apprenant->getCours()->contains($cours)) {
                return $this->json([
                    'message' => 'L\'apprenant est déjà inscrit à ce cours'
                ], Response::HTTP_CONFLICT);
            }

            // Associer le cours à l'apprenant
            $apprenant->addCour($cours);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Cours associé avec succès',
                'cours' => $cours
            ], Response::HTTP_OK, [], ['groups' => 'cours:read']);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{apprenantId}/cours/{coursId}', name: 'api_admin_apprenant_remove_cours', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMINISTRATEUR')]
    public function removeCourseFromApprenant(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                return $this->json([
                    'message' => 'Apprenant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Récupérer le cours
            $cours = $this->coursRepository->find($coursId);
            if (!$cours) {
                return $this->json([
                    'message' => 'Cours non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            // Vérifier si l'apprenant est inscrit à ce cours
            if (!$apprenant->getCours()->contains($cours)) {
                return $this->json([
                    'message' => 'L\'apprenant n\'est pas inscrit à ce cours'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Retirer le cours de l'apprenant
            $apprenant->removeCour($cours);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Cours retiré avec succès'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
