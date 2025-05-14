<?php

namespace App\Controller;

use App\Entity\Certificat;
use App\Entity\Apprenant;
use App\Entity\Cours;
use App\Entity\Progression;
use App\Entity\Notification;
use App\Repository\CertificatRepository;
use App\Repository\ApprenantRepository;
use App\Repository\CoursRepository;
use App\Repository\ProgressionRepository;
use App\Repository\EvaluationRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/api/diagnostic')]
class DiagnosticController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CertificatRepository $certificatRepository,
        private ApprenantRepository $apprenantRepository,
        private CoursRepository $coursRepository,
        private ProgressionRepository $progressionRepository,
        private EvaluationRepository $evaluationRepository,
        private QuizRepository $quizRepository,
        private Security $security
    ) {}

    #[Route('/check-apprenant/{id}', name: 'api_diagnostic_check_apprenant', methods: ['GET'])]
    public function checkApprenant(int $id): JsonResponse
    {
        try {
            $apprenant = $this->apprenantRepository->find($id);
            
            if (!$apprenant) {
                return $this->json([
                    'error' => 'Apprenant not found',
                    'id' => $id
                ], 404);
            }
            
            // Vérifier les propriétés de l'apprenant
            $result = [
                'id' => $apprenant->getId(),
                'name' => $apprenant->getName(),
                'email' => $apprenant->getEmail(),
                'cours_count' => $apprenant->getCours()->count(),
                'has_utilisateur_method' => method_exists($apprenant, 'getUtilisateur'),
                'utilisateur_id' => $apprenant->getUtilisateur()->getId()
            ];
            
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error checking apprenant',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/check-cours/{id}', name: 'api_diagnostic_check_cours', methods: ['GET'])]
    public function checkCours(int $id): JsonResponse
    {
        try {
            $cours = $this->coursRepository->find($id);
            
            if (!$cours) {
                return $this->json([
                    'error' => 'Cours not found',
                    'id' => $id
                ], 404);
            }
            
            // Vérifier les propriétés du cours
            $result = [
                'id' => $cours->getId(),
                'titre' => $cours->getTitre(),
                'description' => $cours->getDescription(),
                'quizzes_count' => $cours->getQuizzes()->count(),
                'apprenants_count' => $cours->getApprenants()->count()
            ];
            
            return $this->json($result, 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error checking cours',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/check-progression/{apprenantId}/{coursId}', name: 'api_diagnostic_check_progression', methods: ['GET'])]
    public function checkProgression(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            $apprenant = $this->apprenantRepository->find($apprenantId);
            $cours = $this->coursRepository->find($coursId);
            
            if (!$apprenant || !$cours) {
                return $this->json([
                    'error' => 'Apprenant or Cours not found',
                    'apprenant_id' => $apprenantId,
                    'cours_id' => $coursId
                ], 404);
            }
            
            // Rechercher la progression
            $connection = $this->entityManager->getConnection();
            $sql = 'SELECT p.id, p.apprenant_id, p.cours_id, p.evaluation_id 
                    FROM progression p
                    WHERE p.cours_id = :coursId
                    AND p.apprenant_id = :apprenantId';
            
            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery([
                'coursId' => $coursId,
                'apprenantId' => $apprenantId
            ]);
            
            $progressionData = $resultSet->fetchAllAssociative();
            
            // Vérifier si une progression existe
            if (empty($progressionData)) {
                return $this->json([
                    'message' => 'No progression found',
                    'apprenant_id' => $apprenantId,
                    'cours_id' => $coursId
                ], 200);
            }
            
            return $this->json([
                'message' => 'Progression found',
                'progressions' => $progressionData
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error checking progression',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    #[Route('/create-progression/{apprenantId}/{coursId}', name: 'api_diagnostic_create_progression', methods: ['POST'])]
    public function createProgression(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            $apprenant = $this->apprenantRepository->find($apprenantId);
            $cours = $this->coursRepository->find($coursId);
            
            if (!$apprenant || !$cours) {
                return $this->json([
                    'error' => 'Apprenant or Cours not found',
                    'apprenant_id' => $apprenantId,
                    'cours_id' => $coursId
                ], 404);
            }
            
            // Récupérer une évaluation pour ce cours
            $quizzes = $this->quizRepository->findBy(['cours' => $cours]);
            $evaluation = null;
            
            foreach ($quizzes as $quiz) {
                $eval = $this->evaluationRepository->findOneBy(['quiz' => $quiz]);
                if ($eval) {
                    $evaluation = $eval;
                    break;
                }
            }
            
            if (!$evaluation) {
                return $this->json([
                    'error' => 'No evaluation found for this course',
                    'cours_id' => $coursId
                ], 400);
            }
            
            // Créer une nouvelle progression
            $progression = new Progression();
            $progression->setCours($cours);
            $progression->setApprenant($apprenant);
            $progression->setEvaluation($evaluation);
            $progression->setTableEvaluations([
                'success_rate' => 100,
                'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                'quizzes_total' => count($quizzes),
                'quizzes_passed' => count($quizzes)
            ]);
            
            $this->entityManager->persist($progression);
            $this->entityManager->flush();
            
            return $this->json([
                'message' => 'Progression created successfully',
                'progression_id' => $progression->getId()
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Error creating progression',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
