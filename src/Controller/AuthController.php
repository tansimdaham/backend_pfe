<?php

namespace App\Controller;

use App\Entity\Administrateur;
use App\Entity\Apprenant;
use App\Entity\Certificat;
use App\Entity\Evaluation;
use App\Entity\EvaluationDetail;
use App\Entity\Formateur;
use App\Entity\Messagerie;
use App\Entity\Notification;
use App\Entity\Progression;
use App\Entity\Utilisateur;
use App\Repository\AdministrateurRepository;
use App\Repository\UtilisateurRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Firebase\JWT\JWT;

#[Route('/api')]
class AuthController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private ValidatorInterface $validator;
    private UtilisateurRepository $utilisateurRepository;
    private AdministrateurRepository $administrateurRepository;
    private string $jwtSecret;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
        UtilisateurRepository $utilisateurRepository,
        AdministrateurRepository $administrateurRepository
    ) {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->validator = $validator;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->administrateurRepository = $administrateurRepository;
        // Utiliser une clé JWT fixe pour le développement - à remplacer par une variable d'environnement en production
        $this->jwtSecret = 'pfe_jwt_secret_key_2024';
    }

    #[Route('/register', name: 'api_register', methods: ['POST', 'OPTIONS'])]
    public function register(Request $request): JsonResponse
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

            if (!isset($data['role']) || !in_array($data['role'], ['administrateur', 'formateur', 'apprenant'])) {
                return $this->json(['message' => 'Rôle invalide'], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $this->utilisateurRepository->findByEmail($data['email'] ?? '');
            if ($existingUser) {
                return $this->json(['message' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
            }

            $existingUserWithSameName = $this->utilisateurRepository->findByName($data['name'] ?? '');
            if ($existingUserWithSameName) {
                return $this->json(['message' => 'Ce nom d\'utilisateur est déjà utilisé'], Response::HTTP_CONFLICT);
            }

            $user = match ($data['role']) {
                'administrateur' => new Administrateur(),
                'formateur' => new Formateur(),
                'apprenant' => new Apprenant(),
                default => throw new \InvalidArgumentException('Rôle invalide')
            };

            $user->setName($data['name'] ?? '');
            $user->setEmail($data['email'] ?? '');
            $user->setPhone($data['phone'] ?? 0);
            $user->setRole($data['role']);

            // Vérifier si c'est le premier administrateur
            $isFirstAdmin = false;
            if ($data['role'] === 'administrateur') {
                $adminCount = $this->administrateurRepository->count([]);
                $isFirstAdmin = ($adminCount === 0);
            }

            // Le premier administrateur est automatiquement approuvé
            $user->setIsApproved($isFirstAdmin);

            // Traitement de l'image de profil
            if (isset($data['profileImage']) && !empty($data['profileImage'])) {
                // Extraire les données de l'image base64
                $imageData = $data['profileImage'];

                // Vérifier si l'image est au format base64
                if (strpos($imageData, 'data:image') === 0) {
                    // Extraire le type MIME et les données binaires
                    list($type, $imageData) = explode(';', $imageData);
                    list(, $imageData) = explode(',', $imageData);
                    $imageData = base64_decode($imageData);

                    // Générer un nom de fichier unique
                    $filename = uniqid() . '.png';

                    // Définir le chemin de sauvegarde
                    $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile-images/';

                    // Créer le répertoire s'il n'existe pas
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    // Sauvegarder l'image
                    file_put_contents($uploadDir . $filename, $imageData);

                    // Enregistrer le chemin relatif dans la base de données
                    $user->setProfileImage('/uploads/profile-images/' . $filename);
                }
            } else {
                $user->setProfileImage(null);
            }

            if (isset($data['password']) && !empty($data['password'])) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
                $user->setPassword($hashedPassword);
            } else {
                return $this->json(['message' => 'Le mot de passe est requis'], Response::HTTP_BAD_REQUEST);
            }

            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
            }

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // Message différent selon que l'utilisateur est approuvé ou non
            $message = $user->isApproved()
                ? 'Utilisateur créé avec succès. Vous pouvez vous connecter immédiatement.'
                : 'Utilisateur créé avec succès. Votre compte est en attente d\'approbation par un administrateur.';

            return $this->json([
                'message' => $message,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'isApproved' => $user->isApproved()
                ]
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', name: 'api_login', methods: ['POST', 'OPTIONS'])]
    public function login(Request $request): JsonResponse
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

            // Modification ici: utilisation de 'email' au lieu de 'username'
            if (!isset($data['email']) || !isset($data['password'])) {
                return $this->json(['message' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
            }

            // Recherche de l'utilisateur par email
            $user = $this->utilisateurRepository->findByEmail($data['email']);
            if (!$user) {
                error_log('Login: Utilisateur non trouvé avec email ' . $data['email']);
                return $this->json(['message' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
            }

            error_log('Login: Utilisateur trouvé - ID: ' . $user->getId() . ', Email: ' . $user->getEmail() . ', Rôle: ' . $user->getRole() . ', Approuvé: ' . ($user->isApproved() ? 'Oui' : 'Non'));

            // Vérification du mot de passe
            if (!$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                error_log('Login: Mot de passe invalide pour l\'utilisateur ' . $user->getEmail());
                return $this->json(['message' => 'Identifiants invalides'], Response::HTTP_UNAUTHORIZED);
            }

            error_log('Login: Mot de passe valide pour l\'utilisateur ' . $user->getEmail());

            // Vérifier si l'utilisateur est approuvé
            if (!$user->isApproved()) {
                error_log('Login: Utilisateur non approuvé - ID: ' . $user->getId());
                return $this->json([
                    'message' => 'Votre compte est en attente d\'approbation par un administrateur',
                    'status' => 'pending'
                ], Response::HTTP_FORBIDDEN);
            }

            error_log('Login: Utilisateur approuvé - ID: ' . $user->getId());

            // Vérifier les rôles
            $roles = $user->getRoles();
            error_log('Login: Rôles de l\'utilisateur: ' . implode(', ', $roles));

            $token = $this->generateJwtToken($user);

            return $this->json([
                'token' => $token,
                'user' => [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                    'phone' => $user->getPhone(),
                    'role' => $user->getRole(),
                    'roles' => $user->getRoles(),
                    'profileImage' => $user->getProfileImage(),
                    'isApproved' => $user->isApproved()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/me', name: 'api_user_me', methods: ['GET', 'OPTIONS'])]
    public function getCurrentUser(Request $request): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            $token = $this->extractTokenFromRequest($request);
            error_log('getCurrentUser: Token extrait - ' . ($token ? 'Présent' : 'Absent'));

            if (!$token) {
                error_log('getCurrentUser: Aucun token trouvé dans la requête');
                return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            try {
                error_log('getCurrentUser: Décodage du token...');
                $payload = $this->decodeJwtToken($token);
                error_log('getCurrentUser: Token décodé avec succès - Payload: ' . json_encode($payload));

                error_log('getCurrentUser: Recherche de l\'utilisateur avec ID ' . ($payload['id'] ?? 'non défini'));
                $user = $this->utilisateurRepository->find($payload['id'] ?? 0);

                if (!$user) {
                    error_log('getCurrentUser: Utilisateur non trouvé avec ID ' . ($payload['id'] ?? 'non défini'));
                    return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
                }

                error_log('getCurrentUser: Utilisateur trouvé - ID: ' . $user->getId() . ', Email: ' . $user->getEmail() . ', Rôle: ' . $user->getRole());
                error_log('getCurrentUser: Rôles de l\'utilisateur: ' . implode(', ', $user->getRoles()));
                error_log('getCurrentUser: Statut d\'approbation: ' . ($user->isApproved() ? 'Approuvé' : 'Non approuvé'));

                return $this->json([
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'name' => $user->getName(),
                        'phone' => $user->getPhone(),
                        'role' => $user->getRole(),
                        'roles' => $user->getRoles(),
                        'profileImage' => $user->getProfileImage(),
                        'isApproved' => $user->isApproved()
                    ]
                ]);
            } catch (\Exception $e) {
                error_log('getCurrentUser: Exception lors du décodage du token ou de la récupération de l\'utilisateur: ' . $e->getMessage());
                error_log('getCurrentUser: Trace: ' . $e->getTraceAsString());
                return $this->json(['message' => 'Token invalide: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/{id}', name: 'api_user_update', methods: ['PUT', 'OPTIONS'])]
    public function updateUser(Request $request, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            $token = $this->extractTokenFromRequest($request);

            if (!$token) {
                return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            try {
                $payload = $this->decodeJwtToken($token);
                $currentUser = $this->utilisateurRepository->find($payload['id']);

                if (!$currentUser) {
                    return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
                }

                if ($currentUser->getId() !== $id && !in_array('ROLE_ADMINISTRATEUR', $currentUser->getRoles())) {
                    return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
                }

                $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
                if (!$user) {
                    return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
                }

                $data = json_decode($request->getContent(), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->json([
                        'message' => 'JSON invalide: ' . json_last_error_msg()
                    ], Response::HTTP_BAD_REQUEST);
                }

                if (isset($data['name'])) {
                    // Vérifier si le nom est déjà utilisé par un autre utilisateur
                    $existingUserWithSameName = $this->utilisateurRepository->findByName($data['name']);
                    if ($existingUserWithSameName && $existingUserWithSameName->getId() !== $user->getId()) {
                        return $this->json(['message' => 'Ce nom d\'utilisateur est déjà utilisé'], Response::HTTP_CONFLICT);
                    }
                    $user->setName($data['name']);
                }

                if (isset($data['phone'])) {
                    $user->setPhone($data['phone']);
                }

                // Gestion de la suppression de l'image de profil
                if (isset($data['deleteProfileImage']) && $data['deleteProfileImage'] === true) {
                    // Si l'utilisateur a une image de profil
                    if ($user->getProfileImage()) {
                        // Récupérer le chemin complet du fichier
                        $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfileImage();

                        // Supprimer le fichier s'il existe
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }

                        // Mettre à null l'image de profil dans la base de données
                        $user->setProfileImage(null);
                    }
                }
                // Gestion de la mise à jour de l'image de profil
                elseif (isset($data['profileImage']) && !empty($data['profileImage'])) {
                    // Extraire les données de l'image base64
                    $imageData = $data['profileImage'];

                    // Vérifier si l'image est au format base64
                    if (strpos($imageData, 'data:image') === 0) {
                        // Si l'utilisateur a déjà une image de profil, la supprimer
                        if ($user->getProfileImage()) {
                            $oldImagePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfileImage();
                            if (file_exists($oldImagePath)) {
                                unlink($oldImagePath);
                            }
                        }

                        // Extraire le type MIME et les données binaires
                        list($type, $imageData) = explode(';', $imageData);
                        list(, $imageData) = explode(',', $imageData);
                        $imageData = base64_decode($imageData);

                        // Générer un nom de fichier unique
                        $filename = uniqid() . '.png';

                        // Définir le chemin de sauvegarde
                        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile-images/';

                        // Créer le répertoire s'il n'existe pas
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        // Sauvegarder l'image
                        file_put_contents($uploadDir . $filename, $imageData);

                        // Enregistrer le chemin relatif dans la base de données
                        $user->setProfileImage('/uploads/profile-images/' . $filename);
                    }
                }

                if (isset($data['currentPassword']) && isset($data['newPassword'])) {
                    if (!$this->passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                        return $this->json(['message' => 'Mot de passe actuel incorrect'], Response::HTTP_BAD_REQUEST);
                    }

                    $hashedPassword = $this->passwordHasher->hashPassword($user, $data['newPassword']);
                    $user->setPassword($hashedPassword);
                }

                $errors = $this->validator->validate($user);
                if (count($errors) > 0) {
                    $errorMessages = [];
                    foreach ($errors as $error) {
                        $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                    }
                    return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
                }

                $this->entityManager->flush();

                return $this->json([
                    'message' => 'Utilisateur mis à jour avec succès',
                    'user' => [
                        'id' => $user->getId(),
                        'email' => $user->getEmail(),
                        'name' => $user->getName(),
                        'phone' => $user->getPhone(),
                        'role' => $user->getRole(),
                        'profileImage' => $user->getProfileImage(),
                        'isApproved' => $user->isApproved()
                    ]
                ]);
            } catch (\Exception $e) {
                return $this->json(['message' => 'Token invalide: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/user/{id}/delete', name: 'api_user_delete', methods: ['DELETE', 'OPTIONS'])]
    public function deleteUser(Request $request, int $id): JsonResponse
    {
        if ($request->getMethod() === 'OPTIONS') {
            return $this->json([], Response::HTTP_OK);
        }

        try {
            error_log('deleteUser: Début de la méthode pour utilisateur ID=' . $id);

            // Afficher tous les en-têtes pour le débogage
            error_log('deleteUser: Tous les en-têtes:');
            foreach ($request->headers->all() as $key => $value) {
                error_log("  $key: " . (is_array($value) ? implode(', ', $value) : $value));
            }

            $token = $this->extractTokenFromRequest($request);
            error_log('deleteUser: Token extrait - ' . ($token ? 'Présent' : 'Absent'));

            if (!$token) {
                error_log('deleteUser: Aucun token trouvé dans la requête');
                return $this->json(['message' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            try {
                error_log('deleteUser: Décodage du token...');
                $payload = $this->decodeJwtToken($token);
                error_log('deleteUser: Token décodé avec succès - Payload: ' . json_encode($payload));

                error_log('deleteUser: Recherche de l\'utilisateur courant avec ID ' . ($payload['id'] ?? 'non défini'));
                $currentUser = $this->utilisateurRepository->find($payload['id'] ?? 0);

                if (!$currentUser) {
                    error_log('deleteUser: Utilisateur courant non trouvé avec ID ' . ($payload['id'] ?? 'non défini'));
                    return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
                }

                error_log('deleteUser: Utilisateur courant trouvé - ID: ' . $currentUser->getId() . ', Email: ' . $currentUser->getEmail() . ', Rôle: ' . $currentUser->getRole());
                error_log('deleteUser: Rôles de l\'utilisateur courant: ' . implode(', ', $currentUser->getRoles()));

                // Vérifier que l'utilisateur supprime son propre compte ou est un administrateur
                if ($currentUser->getId() !== $id && !in_array('ROLE_ADMINISTRATEUR', $currentUser->getRoles())) {
                    error_log('deleteUser: Accès refusé - L\'utilisateur ' . $currentUser->getId() . ' tente de supprimer l\'utilisateur ' . $id);
                    return $this->json(['message' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
                }

                error_log('deleteUser: Recherche de l\'utilisateur à supprimer avec ID ' . $id);
                $user = $this->entityManager->getRepository(Utilisateur::class)->find($id);
                if (!$user) {
                    error_log('deleteUser: Utilisateur à supprimer non trouvé avec ID ' . $id);
                    return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
                }

                error_log('deleteUser: Utilisateur à supprimer trouvé - ID: ' . $user->getId() . ', Email: ' . $user->getEmail() . ', Rôle: ' . $user->getRole());

                // Si l'utilisateur a une image de profil, la supprimer
                if ($user->getProfileImage()) {
                    $imagePath = $this->getParameter('kernel.project_dir') . '/public' . $user->getProfileImage();
                    error_log('deleteUser: Tentative de suppression de l\'image de profil: ' . $imagePath);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                        error_log('deleteUser: Image de profil supprimée avec succès');
                    } else {
                        error_log('deleteUser: Fichier image non trouvé: ' . $imagePath);
                    }
                }

                // Vérifier le type d'utilisateur et gérer les relations avant la suppression
                error_log('deleteUser: Préparation à la suppression de l\'utilisateur ' . $user->getId() . ' de type ' . $user->getRole());

                try {
                    // Si c'est un formateur, gérer les relations avec Messagerie et Evaluation
                    if ($user->getRole() === 'formateur') {
                        $formateur = $this->entityManager->getRepository(Formateur::class)->find($id);
                        if ($formateur) {
                            error_log('deleteUser: Gestion des relations pour le formateur ' . $formateur->getId());

                            // Récupérer et supprimer les messages associés
                            $messagerieRepo = $this->entityManager->getRepository(Messagerie::class);
                            $messages = $messagerieRepo->findBy(['formateur' => $formateur]);
                            error_log('deleteUser: ' . count($messages) . ' messages trouvés pour le formateur');

                            foreach ($messages as $message) {
                                error_log('deleteUser: Suppression du message ' . $message->getId());
                                // Supprimer les notifications liées au message
                                foreach ($message->getNotifications() as $notification) {
                                    $this->entityManager->remove($notification);
                                }
                                $this->entityManager->remove($message);
                            }

                            // Récupérer et gérer les évaluations associées
                            $evaluationRepo = $this->entityManager->getRepository(Evaluation::class);
                            $evaluations = $evaluationRepo->findBy(['formateur' => $formateur]);
                            error_log('deleteUser: ' . count($evaluations) . ' évaluations trouvées pour le formateur');

                            foreach ($evaluations as $evaluation) {
                                error_log('deleteUser: Suppression de l\'évaluation ' . $evaluation->getId());
                                // Supprimer les notifications liées à l'évaluation
                                foreach ($evaluation->getNotifications() as $notification) {
                                    $this->entityManager->remove($notification);
                                }

                                // Supprimer les progressions liées à l'évaluation
                                foreach ($evaluation->getProgressions() as $progression) {
                                    $this->entityManager->remove($progression);
                                }

                                // Supprimer les détails d'évaluation
                                foreach ($evaluation->getEvaluationDetails() as $detail) {
                                    $this->entityManager->remove($detail);
                                }

                                $this->entityManager->remove($evaluation);
                            }
                        }
                    }
                    // Si c'est un apprenant, gérer les relations avec Messagerie, Progression, etc.
                    else if ($user->getRole() === 'apprenant') {
                        $apprenant = $this->entityManager->getRepository(Apprenant::class)->find($id);
                        if ($apprenant) {
                            error_log('deleteUser: Gestion des relations pour l\'apprenant ' . $apprenant->getId());

                            // Récupérer et supprimer les messages associés
                            $messagerieRepo = $this->entityManager->getRepository(Messagerie::class);
                            $messages = $messagerieRepo->findBy(['apprenant' => $apprenant]);
                            error_log('deleteUser: ' . count($messages) . ' messages trouvés pour l\'apprenant');

                            foreach ($messages as $message) {
                                error_log('deleteUser: Suppression du message ' . $message->getId());
                                // Supprimer les notifications liées au message
                                foreach ($message->getNotifications() as $notification) {
                                    $this->entityManager->remove($notification);
                                }
                                $this->entityManager->remove($message);
                            }

                            // Récupérer et gérer les évaluations associées
                            $evaluationRepo = $this->entityManager->getRepository(Evaluation::class);
                            $evaluations = $evaluationRepo->findBy(['apprenant' => $apprenant]);
                            error_log('deleteUser: ' . count($evaluations) . ' évaluations trouvées pour l\'apprenant');

                            foreach ($evaluations as $evaluation) {
                                error_log('deleteUser: Suppression de l\'évaluation ' . $evaluation->getId());
                                // Supprimer les notifications liées à l'évaluation
                                foreach ($evaluation->getNotifications() as $notification) {
                                    $this->entityManager->remove($notification);
                                }

                                // Supprimer les progressions liées à l'évaluation
                                foreach ($evaluation->getProgressions() as $progression) {
                                    $this->entityManager->remove($progression);
                                }

                                // Supprimer les détails d'évaluation
                                foreach ($evaluation->getEvaluationDetails() as $detail) {
                                    $this->entityManager->remove($detail);
                                }

                                $this->entityManager->remove($evaluation);
                            }

                            // Supprimer les progressions de l'apprenant
                            $progressionRepo = $this->entityManager->getRepository(Progression::class);
                            $progressions = $progressionRepo->findBy(['apprenant' => $apprenant]);
                            error_log('deleteUser: ' . count($progressions) . ' progressions trouvées pour l\'apprenant');

                            foreach ($progressions as $progression) {
                                $this->entityManager->remove($progression);
                            }

                            // Supprimer les certificats de l'apprenant
                            $certificatRepo = $this->entityManager->getRepository(Certificat::class);
                            $certificats = $certificatRepo->findBy(['apprenant' => $apprenant]);
                            error_log('deleteUser: ' . count($certificats) . ' certificats trouvés pour l\'apprenant');

                            foreach ($certificats as $certificat) {
                                $this->entityManager->remove($certificat);
                            }
                        }
                    }

                    // Supprimer les notifications de l'utilisateur
                    $notificationRepo = $this->entityManager->getRepository(Notification::class);
                    $notifications = $notificationRepo->findBy(['utilisateur' => $user]);
                    error_log('deleteUser: ' . count($notifications) . ' notifications trouvées pour l\'utilisateur');

                    foreach ($notifications as $notification) {
                        $this->entityManager->remove($notification);
                    }

                    // Appliquer les suppressions
                    $this->entityManager->flush();

                    // Supprimer l'utilisateur
                    error_log('deleteUser: Suppression de l\'utilisateur ' . $user->getId());
                    $this->entityManager->remove($user);
                    $this->entityManager->flush();
                    error_log('deleteUser: Utilisateur ' . $id . ' supprimé avec succès');
                } catch (\Exception $e) {
                    error_log('deleteUser: Erreur lors de la suppression des relations: ' . $e->getMessage());
                    error_log('deleteUser: Trace: ' . $e->getTraceAsString());

                    // Essayer une approche alternative avec des requêtes SQL directes
                    try {
                        error_log('deleteUser: Tentative de suppression avec des requêtes SQL directes');
                        $conn = $this->entityManager->getConnection();

                        // Désactiver temporairement les contraintes de clé étrangère
                        $conn->executeQuery('SET FOREIGN_KEY_CHECKS=0');

                        // Supprimer les enregistrements liés dans les tables spécifiques
                        if ($user->getRole() === 'formateur') {
                            $conn->executeQuery('DELETE FROM messagerie WHERE formateur_id = ?', [$id]);
                            $conn->executeQuery('DELETE FROM evaluation WHERE formateur_id = ?', [$id]);
                        } else if ($user->getRole() === 'apprenant') {
                            $conn->executeQuery('DELETE FROM messagerie WHERE apprenant_id = ?', [$id]);
                            $conn->executeQuery('DELETE FROM evaluation WHERE apprenant_id = ?', [$id]);
                            $conn->executeQuery('DELETE FROM progression WHERE apprenant_id = ?', [$id]);
                            $conn->executeQuery('DELETE FROM certificat WHERE apprenant_id = ?', [$id]);
                        }

                        // Supprimer les notifications
                        $conn->executeQuery('DELETE FROM notification WHERE user_id = ?', [$id]);

                        // Supprimer l'utilisateur dans les tables spécifiques
                        if ($user->getRole() === 'formateur') {
                            $conn->executeQuery('DELETE FROM formateur WHERE id = ?', [$id]);
                        } else if ($user->getRole() === 'apprenant') {
                            $conn->executeQuery('DELETE FROM apprenant WHERE id = ?', [$id]);
                        } else if ($user->getRole() === 'administrateur') {
                            $conn->executeQuery('DELETE FROM administrateur WHERE id = ?', [$id]);
                        }

                        // Supprimer l'utilisateur dans la table principale
                        $conn->executeQuery('DELETE FROM utilisateur WHERE id = ?', [$id]);

                        // Réactiver les contraintes de clé étrangère
                        $conn->executeQuery('SET FOREIGN_KEY_CHECKS=1');

                        error_log('deleteUser: Utilisateur ' . $id . ' supprimé avec succès via SQL direct');
                    } catch (\Exception $sqlEx) {
                        error_log('deleteUser: Erreur lors de la suppression SQL directe: ' . $sqlEx->getMessage());
                        error_log('deleteUser: Trace SQL: ' . $sqlEx->getTraceAsString());
                        throw $sqlEx; // Relancer l'exception pour la gérer au niveau supérieur
                    }
                }

                // Si l'utilisateur supprime son propre compte, déconnecter
                if ($currentUser->getId() === $id) {
                    error_log('deleteUser: L\'utilisateur a supprimé son propre compte');
                    // Pas besoin d'invalider le token côté serveur car nous utilisons JWT
                    // Le client devra supprimer le token lui-même
                }

                return $this->json([
                    'message' => 'Compte supprimé avec succès'
                ]);
            } catch (\Exception $e) {
                error_log('deleteUser: Exception lors du décodage du token ou de la suppression: ' . $e->getMessage());
                error_log('deleteUser: Trace: ' . $e->getTraceAsString());
                return $this->json(['message' => 'Token invalide: ' . $e->getMessage()], Response::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function generateJwtToken(UserInterface $user): string
    {
        // Créer le payload avec plus d'informations
        $payload = [
            'id' => $user->getId(),
            'email' => $user->getUserIdentifier(),
            'name' => $user->getName(),
            'role' => $user->getRole(),
            'roles' => $user->getRoles(),
            'isApproved' => $user->isApproved(),
            'iat' => time(),                  // Issued At (quand le token a été émis)
            'exp' => time() + 3600 * 24       // Expiration (24 heures)
        ];

        error_log('generateJwtToken: Payload généré - ' . json_encode($payload));

        // Encoder le token
        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
        error_log('generateJwtToken: Token généré avec succès');

        return $token;
    }

    private function decodeJwtToken(string $token): array
    {
        try {
            error_log('decodeJwtToken: Tentative de décodage du token');
            $decoded = JWT::decode($token, new \Firebase\JWT\Key($this->jwtSecret, 'HS256'));
            $payload = (array) $decoded;
            error_log('decodeJwtToken: Token décodé avec succès - ' . json_encode($payload));
            return $payload;
        } catch (\Firebase\JWT\ExpiredException $e) {
            error_log('decodeJwtToken: Token expiré: ' . $e->getMessage());
            throw new \Exception('Token JWT expiré: ' . $e->getMessage());
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            error_log('decodeJwtToken: Signature du token invalide: ' . $e->getMessage());
            throw new \Exception('Signature du token invalide: ' . $e->getMessage());
        } catch (\Exception $e) {
            error_log('decodeJwtToken: Erreur de décodage du token: ' . $e->getMessage());
            error_log('decodeJwtToken: Trace: ' . $e->getTraceAsString());
            throw new \Exception('Token JWT invalide: ' . $e->getMessage());
        }
    }

    private function extractTokenFromRequest(Request $request): ?string
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader) {
            error_log('extractTokenFromRequest: Aucun en-tête Authorization trouvé');
            return null;
        }

        error_log('extractTokenFromRequest: En-tête Authorization trouvé - ' . $authHeader);

        $parts = explode(' ', $authHeader);
        if (count($parts) !== 2 || $parts[0] !== 'Bearer') {
            error_log('extractTokenFromRequest: Format d\'en-tête Authorization invalide');
            return null;
        }

        error_log('extractTokenFromRequest: Token extrait avec succès');
        return $parts[1];
    }


    #[Route('/forgot-password', name: 'api_forgot_password', methods: ['POST', 'OPTIONS'])]
    public function forgotPassword(Request $request, EmailService $emailService): JsonResponse
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

            if (!isset($data['email']) || empty($data['email'])) {
                return $this->json(['message' => 'Email requis'], Response::HTTP_BAD_REQUEST);
            }

            $user = $this->utilisateurRepository->findByEmail($data['email']);
            if (!$user) {
                // Pour des raisons de sécurité, ne pas révéler si l'email existe ou non
                return $this->json([
                    'message' => 'Si votre email est associé à un compte, vous recevrez un lien de réinitialisation de mot de passe.'
                ], Response::HTTP_OK);
            }

            // Générer un token de réinitialisation
            $resetToken = bin2hex(random_bytes(32));
            $expiresAt = new \DateTime('+1 hour');

            // Stocker le token dans la base de données
            $user->setResetToken($resetToken);
            $user->setResetTokenExpiresAt($expiresAt);
            $this->entityManager->flush();

            // Envoyer l'email de réinitialisation
            try {
                $emailService->sendPasswordResetEmail($user, $resetToken);
            } catch (\Exception $emailError) {
                // Continuer même si l'envoi d'email échoue
                error_log('Erreur lors de l\'envoi de l\'email de réinitialisation: ' . $emailError->getMessage());
            }

            return $this->json([
                'message' => 'Si votre email est associé à un compte, vous recevrez un lien de réinitialisation de mot de passe.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/reset-password/{token}', name: 'api_reset_password', methods: ['POST', 'OPTIONS'])]
    public function resetPassword(Request $request, string $token): JsonResponse
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

            if (!isset($data['password']) || empty($data['password'])) {
                return $this->json(['message' => 'Nouveau mot de passe requis'], Response::HTTP_BAD_REQUEST);
            }

            // Rechercher l'utilisateur par token
            $user = $this->utilisateurRepository->findOneBy(['resetToken' => $token]);
            if (!$user) {
                return $this->json(['message' => 'Token invalide ou expiré'], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier si le token a expiré
            $now = new \DateTime();
            if ($user->getResetTokenExpiresAt() < $now) {
                return $this->json(['message' => 'Token expiré. Veuillez demander un nouveau lien de réinitialisation.'], Response::HTTP_BAD_REQUEST);
            }

            // Mettre à jour le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            // Effacer le token de réinitialisation
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);

            $this->entityManager->flush();

            return $this->json([
                'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}