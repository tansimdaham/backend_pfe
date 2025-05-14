<?php

namespace App\Controller;

use App\Entity\Apprenant;
use App\Repository\ApprenantRepository;
use App\Repository\CoursRepository;
use App\Repository\QuizRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/apprenant')]
class ApprenantController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UtilisateurRepository $utilisateurRepository;
    private ApprenantRepository $apprenantRepository;
    private CoursRepository $coursRepository;
    private Security $security;
    private SerializerInterface $serializer;

    public function __construct(
        EntityManagerInterface $entityManager,
        UtilisateurRepository $utilisateurRepository,
        ApprenantRepository $apprenantRepository,
        CoursRepository $coursRepository,
        Security $security,
        SerializerInterface $serializer
    ) {
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->apprenantRepository = $apprenantRepository;
        $this->coursRepository = $coursRepository;
        $this->security = $security;
        $this->serializer = $serializer;
    }

    #[Route('/cours', name: 'api_apprenant_cours', methods: ['GET'])]
    public function getMesCours(): JsonResponse
    {
        try {
            // Récupérer l'utilisateur connecté
            $user = $this->security->getUser();

            if (!$user) {
                return $this->json([
                    'message' => 'Utilisateur non authentifié'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Vérifier que l'utilisateur est bien un apprenant
            if (!$user instanceof Apprenant) {
                return $this->json([
                    'message' => 'L\'utilisateur n\'est pas un apprenant'
                ], Response::HTTP_FORBIDDEN);
            }

            // MODIFICATION: Récupérer tous les cours au lieu de seulement ceux de l'apprenant
            $cours = $this->coursRepository->findAll();

            // Log pour le débogage
            error_log('ApprenantController::getMesCours - Apprenant ID: ' . $user->getId());
            error_log('ApprenantController::getMesCours - Retour de tous les cours sans filtrer par apprenant');

            return $this->json([
                'apprenant' => [
                    'id' => $user->getId(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()
                ],
                'cours' => $cours
            ], Response::HTTP_OK, [], ['groups' => 'cours:read']);
        } catch (\Exception $e) {
            error_log('ApprenantController::getMesCours - Exception: ' . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
