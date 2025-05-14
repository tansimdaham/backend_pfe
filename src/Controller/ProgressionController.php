<?php

namespace App\Controller;

use App\Entity\Progression;
use App\Entity\Apprenant;
use App\Entity\Cours;
use App\Entity\Evaluation;
use App\Entity\Certificat;
use App\Repository\ProgressionRepository;
use App\Repository\ApprenantRepository;
use App\Repository\CoursRepository;
use App\Repository\EvaluationRepository;
use App\Repository\QuizRepository;
use App\Repository\CertificatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/progression')]
class ProgressionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProgressionRepository $progressionRepository,
        private ApprenantRepository $apprenantRepository,
        private CoursRepository $coursRepository,
        private EvaluationRepository $evaluationRepository,
        private QuizRepository $quizRepository,
        private CertificatRepository $certificatRepository,
        private Security $security,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'api_progression_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $progressions = $this->progressionRepository->findAll();

            return $this->json([
                'progressions' => $progressions
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_progression_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $progression = $this->progressionRepository->find($id);

            if (!$progression) {
                return $this->json(['error' => 'Progression not found'], 404);
            }

            return $this->json([
                'progression' => $progression
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/apprenant/{apprenantId}', name: 'api_progression_by_apprenant', methods: ['GET'])]
    public function getProgressionByApprenant(int $apprenantId): JsonResponse
    {
        try {
            // Log the start of the method execution
            error_log("[ProgressionController] Starting getProgressionByApprenant for apprenant {$apprenantId}");

            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                error_log("[ProgressionController] Apprenant {$apprenantId} not found");
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Vérifier que l'utilisateur actuel est bien l'apprenant demandé ou un formateur
            $currentUser = $this->security->getUser();
            $isFormateur = $currentUser && in_array('ROLE_FORMATEUR', $currentUser->getRoles());

            if (!$currentUser || (!$isFormateur && $currentUser->getId() !== $apprenant->getId())) {
                error_log("[ProgressionController] Access denied for user trying to access apprenant {$apprenantId}");
                return $this->json(['error' => 'Access denied'], 403);
            }

            // Utiliser une requête directe pour récupérer tous les cours
            $connection = $this->entityManager->getConnection();

            // Optimisation: Récupérer tous les cours, quiz et évaluations en une seule requête
            $sql = 'SELECT
                        c.id as course_id,
                        c.titre as course_title,
                        c.description as course_description,
                        q.id as quiz_id,
                        q.nom_fr as quiz_name,
                        q.idmodule as idmodule,
                        e.statut_evaluation as status
                    FROM
                        cours c
                    LEFT JOIN
                        quiz q ON q.cours_id = c.id
                    LEFT JOIN (
                        SELECT e1.*
                        FROM evaluation e1
                        LEFT JOIN evaluation e2 ON (
                            e1.quiz_id = e2.quiz_id AND
                            e1.apprenant_id = e2.apprenant_id AND
                            e1.id < e2.id
                        )
                        WHERE
                            e2.id IS NULL AND
                            e1.apprenant_id = :apprenantId
                    ) e ON q.id = e.quiz_id
                    ORDER BY
                        c.id, q.id';

            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery(['apprenantId' => $apprenantId]);
            $allData = $resultSet->fetchAllAssociative();

            // Organiser les données par cours
            $coursesMap = [];
            $totalQuizzes = 0;
            $passedQuizzes = 0;

            foreach ($allData as $row) {
                $courseId = $row['course_id'];

                // Si c'est la première fois qu'on rencontre ce cours, l'initialiser
                if (!isset($coursesMap[$courseId])) {
                    $coursesMap[$courseId] = [
                        'course_id' => $courseId,
                        'course_title' => $row['course_title'],
                        'quizzes' => [],
                        'quizzes_total' => 0,
                        'quizzes_passed' => 0
                    ];
                }

                // Si le quiz existe (peut être null pour les cours sans quiz)
                if ($row['quiz_id']) {
                    $quizId = $row['quiz_id'];
                    $status = $row['status'] ?: 'Non évalué';

                    $coursesMap[$courseId]['quizzes'][] = [
                        'quiz_id' => $quizId,
                        'quiz_name' => $row['quiz_name'],
                        'idmodule' => $row['idmodule'],
                        'status' => $status
                    ];

                    $coursesMap[$courseId]['quizzes_total']++;
                    $totalQuizzes++;

                    if ($status === 'Satisfaisant') {
                        $coursesMap[$courseId]['quizzes_passed']++;
                        $passedQuizzes++;
                    }
                }
            }

            // Récupérer les certificats en une seule requête
            $sql = 'SELECT
                        c.id as certificat_id,
                        c.date_obtention,
                        p.cours_id
                    FROM
                        certificat c
                    JOIN
                        progression p ON c.progression_id = p.id
                    WHERE
                        c.apprenant_id = :apprenantId';

            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery(['apprenantId' => $apprenantId]);
            $certificatsData = $resultSet->fetchAllAssociative();

            $certificatsMap = [];
            foreach ($certificatsData as $cert) {
                $certificatsMap[$cert['cours_id']] = [
                    'id' => $cert['certificat_id'],
                    'date_obtention' => $cert['date_obtention']
                ];
            }

            // Finaliser les données de progression pour chaque cours
            $progressions = [];
            $completedCourses = 0;

            foreach ($coursesMap as $courseId => $courseData) {
                $courseQuizCount = $courseData['quizzes_total'];
                $courseQuizzesPassed = $courseData['quizzes_passed'];

                // Calculer le pourcentage de progression pour ce cours
                $courseProgress = $courseQuizCount > 0 ? ($courseQuizzesPassed / $courseQuizCount) * 100 : 0;

                // Si tous les quiz sont passés, marquer le cours comme complété
                $isCompleted = $courseQuizCount > 0 && $courseProgress === 100;
                if ($isCompleted) {
                    $completedCourses++;
                }

                $courseProgression = [
                    'course_id' => $courseId,
                    'course_title' => $courseData['course_title'],
                    'progress_percentage' => $courseProgress,
                    'quizzes_total' => $courseQuizCount,
                    'quizzes_passed' => $courseQuizzesPassed,
                    'quiz_evaluations' => $courseData['quizzes'],
                    'is_completed' => $isCompleted
                ];

                // Ajouter les informations du certificat si disponible
                if (isset($certificatsMap[$courseId])) {
                    $courseProgression['certificat'] = $certificatsMap[$courseId];
                }

                $progressions[] = $courseProgression;
            }

            $totalCourses = count($coursesMap);

            // Calculer le pourcentage global de progression
            $overallProgress = $totalQuizzes > 0 ? ($passedQuizzes / $totalQuizzes) * 100 : 0;

            $response = [
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName()
                ],
                'overall_progress' => $overallProgress,
                'total_courses' => $totalCourses,
                'completed_courses' => $completedCourses,
                'total_quizzes' => $totalQuizzes,
                'passed_quizzes' => $passedQuizzes,
                'course_progressions' => $progressions
            ];

            error_log("[ProgressionController] Successfully generated progression response for apprenant {$apprenantId}");
            return $this->json($response, 200);
        } catch (\Exception $e) {
            error_log("[ProgressionController] Error in getProgressionByApprenant: " . $e->getMessage());
            error_log("[ProgressionController] Stack trace: " . $e->getTraceAsString());

            // Retourner une réponse partielle avec les informations disponibles
            try {
                $apprenant = $this->apprenantRepository->find($apprenantId);

                $response = [
                    'error' => true,
                    'message' => 'Une erreur est survenue lors du calcul de la progression',
                    'apprenant' => $apprenant ? [
                        'id' => $apprenant->getId(),
                        'name' => $apprenant->getName()
                    ] : null,
                    'overall_progress' => 0,
                    'total_courses' => 0,
                    'completed_courses' => 0,
                    'total_quizzes' => 0,
                    'passed_quizzes' => 0,
                    'course_progressions' => []
                ];

                return $this->json($response, 200);
            } catch (\Exception $fallbackException) {
                // Si même la réponse partielle échoue, retourner une erreur simple
                return $this->json([
                    'error' => 'Server error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }

    #[Route('/apprenant/{apprenantId}/cours/{coursId}', name: 'api_progression_by_apprenant_cours', methods: ['GET'])]
    public function getProgressionByApprenantAndCours(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            // Log the start of the method execution
            error_log("[ProgressionController] Starting getProgressionByApprenantAndCours for apprenant {$apprenantId} and cours {$coursId}");

            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                error_log("[ProgressionController] Apprenant {$apprenantId} not found");
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Récupérer le cours
            $cours = $this->coursRepository->find($coursId);
            if (!$cours) {
                error_log("[ProgressionController] Cours {$coursId} not found");
                return $this->json(['error' => 'Cours not found'], 404);
            }

            // Vérifier les permissions d'accès - Seul l'apprenant lui-même ou un formateur peut accéder
            $currentUser = $this->security->getUser();
            $isFormateur = $currentUser && in_array('ROLE_FORMATEUR', $currentUser->getRoles());

            if (!$currentUser || (!$isFormateur && $currentUser->getId() !== $apprenant->getId())) {
                error_log("[ProgressionController] Access denied: User is not authorized to access this progression");
                return $this->json(['error' => 'Access denied'], 403);
            }

            // Vérifier si l'apprenant est inscrit au cours (pour information seulement)
            $connection = $this->entityManager->getConnection();
            $sql = 'SELECT COUNT(*) as count FROM apprenant_cours WHERE apprenant_id = :apprenantId AND cours_id = :coursId';
            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery([
                'apprenantId' => $apprenantId,
                'coursId' => $coursId
            ]);
            $result = $resultSet->fetchAssociative();
            $isEnrolled = $result['count'] > 0;

            error_log("[ProgressionController] Apprenant {$apprenantId} enrollment status for cours {$coursId}: " . ($isEnrolled ? 'enrolled' : 'not enrolled'));

            // Si l'apprenant n'est pas inscrit, on peut l'inscrire automatiquement
            if (!$isEnrolled) {
                error_log("[ProgressionController] Automatically enrolling apprenant {$apprenantId} in course {$coursId}");
                try {
                    // Ajouter l'apprenant au cours
                    $apprenant->addCour($cours);
                    $this->entityManager->flush();
                    error_log("[ProgressionController] Successfully enrolled apprenant {$apprenantId} in course {$coursId}");
                } catch (\Exception $enrollException) {
                    error_log("[ProgressionController] Error enrolling apprenant: " . $enrollException->getMessage());
                    // Continue même si l'inscription échoue
                }
            }

            // Optimisation: Récupérer tous les quiz et leurs évaluations en une seule requête
            $sql = 'SELECT
                        q.id as quiz_id,
                        q.nom_fr as quiz_name,
                        q.idmodule as idmodule,
                        e.statut_evaluation as status
                    FROM
                        quiz q
                    LEFT JOIN (
                        SELECT e1.*
                        FROM evaluation e1
                        LEFT JOIN evaluation e2 ON (
                            e1.quiz_id = e2.quiz_id AND
                            e1.apprenant_id = e2.apprenant_id AND
                            e1.id < e2.id
                        )
                        WHERE
                            e2.id IS NULL AND
                            e1.apprenant_id = :apprenantId
                    ) e ON q.id = e.quiz_id
                    WHERE
                        q.cours_id = :coursId
                    ORDER BY
                        q.id';

            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery([
                'apprenantId' => $apprenantId,
                'coursId' => $coursId
            ]);

            $quizData = $resultSet->fetchAllAssociative();
            $totalQuizzes = count($quizData);
            $passedQuizzes = 0;
            $quizEvaluations = [];

            error_log("[ProgressionController] Found {$totalQuizzes} quizzes for cours {$coursId}");

            // Traiter les données des quiz
            foreach ($quizData as $quiz) {
                $status = $quiz['status'] ?: 'Non évalué';
                if ($status === 'Satisfaisant') {
                    $passedQuizzes++;
                }

                $quizEvaluations[] = [
                    'quiz_id' => $quiz['quiz_id'],
                    'quiz_name' => $quiz['quiz_name'],
                    'idmodule' => $quiz['idmodule'],
                    'status' => $status
                ];
            }

            // Calculer le pourcentage de progression pour ce cours
            $courseProgress = $totalQuizzes > 0 ? ($passedQuizzes / $totalQuizzes) * 100 : 0;
            error_log("[ProgressionController] Course progress: {$courseProgress}% ({$passedQuizzes}/{$totalQuizzes} quizzes passed)");

            // Vérifier si un certificat existe pour ce cours et cet apprenant
            $sql = 'SELECT c.id, c.date_obtention FROM certificat c
                   JOIN progression p ON c.progression_id = p.id
                   WHERE c.apprenant_id = :apprenantId AND p.cours_id = :coursId
                   LIMIT 1';
            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery([
                'apprenantId' => $apprenantId,
                'coursId' => $coursId
            ]);
            $certificatData = $resultSet->fetchAssociative();
            $certificat = null;

            if ($certificatData) {
                $certificat = [
                    'id' => $certificatData['id'],
                    'date_obtention' => $certificatData['date_obtention']
                ];
            }

            // Préparer la réponse
            $response = [
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName()
                ],
                'course' => [
                    'id' => $cours->getId(),
                    'title' => $cours->getTitre()
                ],
                'progress_percentage' => $courseProgress,
                'quizzes_total' => $totalQuizzes,
                'quizzes_passed' => $passedQuizzes,
                'quiz_evaluations' => $quizEvaluations,
                'is_completed' => $courseProgress === 100
            ];

            // Ajouter les informations du certificat si disponible
            if ($certificat) {
                $response['certificat'] = $certificat;
            }

            error_log("[ProgressionController] Successfully generated progression response for apprenant {$apprenantId} and cours {$coursId}");
            return $this->json($response, 200);
        } catch (\Exception $e) {
            error_log("[ProgressionController] Error in getProgressionByApprenantAndCours: " . $e->getMessage());
            error_log("[ProgressionController] Stack trace: " . $e->getTraceAsString());

            // Retourner une réponse partielle avec les informations disponibles
            try {
                $apprenant = $this->apprenantRepository->find($apprenantId);
                $cours = $this->coursRepository->find($coursId);

                $response = [
                    'error' => true,
                    'message' => 'Une erreur est survenue lors du calcul de la progression',
                    'apprenant' => $apprenant ? [
                        'id' => $apprenant->getId(),
                        'name' => $apprenant->getName()
                    ] : null,
                    'course' => $cours ? [
                        'id' => $cours->getId(),
                        'title' => $cours->getTitre()
                    ] : null,
                    'progress_percentage' => 0,
                    'quizzes_total' => 0,
                    'quizzes_passed' => 0,
                    'quiz_evaluations' => [],
                    'is_completed' => false
                ];

                return $this->json($response, 200);
            } catch (\Exception $fallbackException) {
                // Si même la réponse partielle échoue, retourner une erreur simple
                return $this->json([
                    'error' => 'Server error',
                    'message' => $e->getMessage()
                ], 500);
            }
        }
    }
}
