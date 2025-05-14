<?php

namespace App\Controller;

use App\Entity\Apprenant;
use App\Entity\Cours;
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

#[Route('/api/formateur')]
class FormateurController extends AbstractController
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

    #[Route('/apprenants', name: 'api_formateur_apprenants', methods: ['GET'])]
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
                ->getResult();

            return $this->json(['apprenants' => $apprenants]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{id}', name: 'api_formateur_apprenant', methods: ['GET'])]
    public function getApprenant(int $id): JsonResponse
    {
        try {
            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($id);

            if (!$apprenant) {
                return $this->json([
                    'message' => 'Apprenant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            return $this->json([
                'id' => $apprenant->getId(),
                'name' => $apprenant->getName(),
                'email' => $apprenant->getEmail(),
                'phone' => $apprenant->getPhone(),
                'profileImage' => $apprenant->getProfileImage()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{id}/cours', name: 'api_formateur_apprenant_cours', methods: ['GET'])]
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

            // Récupérer uniquement les cours associés à l'apprenant
            $cours = $apprenant->getCours()->toArray();
            error_log("Retour des cours associés à l'apprenant ID: " . $id . ", Nombre de cours: " . count($cours));

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

    #[Route('/cours', name: 'api_formateur_cours', methods: ['GET'])]
    public function getCours(Request $request, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $category = $request->query->get('category');
            error_log("Récupération des cours" . ($category ? " avec catégorie: $category" : " sans filtre de catégorie"));

            if ($category) {
                // Récupérer les cours qui ont des quiz de la catégorie spécifiée
                $cours = $quizRepository->findCoursesByQuizCategory($category);
                error_log("Nombre de cours trouvés avec la catégorie '$category': " . count($cours));

                // Afficher les IDs des cours trouvés pour le débogage
                foreach ($cours as $c) {
                    error_log("Cours trouvé: ID=" . $c->getId() . ", Titre=" . $c->getTitre());
                }
            } else {
                // Récupérer tous les cours
                $cours = $this->coursRepository->findAll();
                error_log("Nombre total de cours: " . count($cours));
            }

            return $this->json($cours, Response::HTTP_OK, [], ['groups' => 'cours:read']);
        } catch (\Exception $e) {
            error_log("Exception dans getCours: " . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{id}/cours/category/{category}', name: 'api_formateur_apprenant_cours_by_category', methods: ['GET'])]
    public function getApprenantCoursByCategory(int $id, string $category, QuizRepository $quizRepository): JsonResponse
    {
        try {
            // Log pour déboguer
            error_log("Recherche des cours pour l'apprenant ID: $id avec la catégorie: $category");

            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($id);

            if (!$apprenant) {
                error_log("Apprenant non trouvé avec l'ID: $id");
                return $this->json([
                    'message' => 'Apprenant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            error_log("Apprenant trouvé: " . $apprenant->getName());

            // Récupérer les cours associés à l'apprenant
            $apprenantCours = $apprenant->getCours()->toArray();
            error_log("Nombre de cours associés à l'apprenant: " . count($apprenantCours));

            // Vérifier si la catégorie est "all" pour récupérer les cours avec des quiz de toutes les catégories
            $coursWithCategory = [];
            if ($category === 'all') {
                $allCategoryCourses = $quizRepository->findCoursesWithMultipleCategories();
                error_log("Nombre total de cours avec toutes les catégories: " . count($allCategoryCourses));

                // Filtrer pour ne garder que les cours associés à l'apprenant
                $coursWithCategory = array_filter($allCategoryCourses, function($cours) use ($apprenantCours) {
                    foreach ($apprenantCours as $apprenantCour) {
                        if ($apprenantCour->getId() === $cours->getId()) {
                            return true;
                        }
                    }
                    return false;
                });
            } else {
                // Récupérer les cours qui ont des quiz de la catégorie spécifiée
                $categoryCourses = $quizRepository->findCoursesByQuizCategory($category);
                error_log("Nombre total de cours avec la catégorie '$category': " . count($categoryCourses));

                // Filtrer pour ne garder que les cours associés à l'apprenant
                $coursWithCategory = array_filter($categoryCourses, function($cours) use ($apprenantCours) {
                    foreach ($apprenantCours as $apprenantCour) {
                        if ($apprenantCour->getId() === $cours->getId()) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            error_log("Nombre de cours associés à l'apprenant avec la catégorie '$category': " . count($coursWithCategory));

            return $this->json([
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName(),
                    'email' => $apprenant->getEmail()
                ],
                'category' => $category,
                'cours' => array_values($coursWithCategory) // Réindexer le tableau après le filtrage
            ], Response::HTTP_OK, [], ['groups' => 'cours:read']);
        } catch (\Exception $e) {
            error_log("Exception dans getApprenantCoursByCategory: " . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/apprenants/{id}/cours/all-categories', name: 'api_formateur_apprenant_cours_all_categories', methods: ['GET'])]
    public function getApprenantCoursAllCategories(int $id, QuizRepository $quizRepository): JsonResponse
    {
        try {
            // Log pour déboguer
            error_log("Recherche des cours pour l'apprenant ID: $id avec toutes les catégories");

            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($id);

            if (!$apprenant) {
                error_log("Apprenant non trouvé avec l'ID: $id");
                return $this->json([
                    'message' => 'Apprenant non trouvé'
                ], Response::HTTP_NOT_FOUND);
            }

            error_log("Apprenant trouvé: " . $apprenant->getName());

            // Récupérer les cours associés à l'apprenant
            $apprenantCours = $apprenant->getCours()->toArray();
            error_log("Nombre de cours associés à l'apprenant: " . count($apprenantCours));

            // Récupérer les cours qui ont des quiz de toutes les catégories
            $allCategoryCourses = $quizRepository->findCoursesWithMultipleCategories();
            error_log("Nombre total de cours avec toutes les catégories: " . count($allCategoryCourses));

            // Filtrer pour ne garder que les cours associés à l'apprenant
            $coursWithAllCategories = array_filter($allCategoryCourses, function($cours) use ($apprenantCours) {
                foreach ($apprenantCours as $apprenantCour) {
                    if ($apprenantCour->getId() === $cours->getId()) {
                        return true;
                    }
                }
                return false;
            });

            error_log("Nombre de cours associés à l'apprenant avec toutes les catégories: " . count($coursWithAllCategories));

            return $this->json([
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName(),
                    'email' => $apprenant->getEmail()
                ],
                'category' => 'all',
                'cours' => array_values($coursWithAllCategories) // Réindexer le tableau après le filtrage
            ], Response::HTTP_OK, [], ['groups' => 'cours:read']);
        } catch (\Exception $e) {
            error_log("Exception dans getApprenantCoursAllCategories: " . $e->getMessage());
            return $this->json([
                'message' => 'Erreur serveur: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
