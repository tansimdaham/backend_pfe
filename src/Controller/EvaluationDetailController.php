<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\EvaluationDetail;
use App\Repository\EvaluationDetailRepository;
use App\Repository\EvaluationRepository;
use App\Repository\QuizRepository;
use App\Repository\ApprenantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/api/evaluation-detail')]
class EvaluationDetailController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvaluationDetailRepository $evaluationDetailRepository,
        private EvaluationRepository $evaluationRepository,
        private QuizRepository $quizRepository,
        private ApprenantRepository $apprenantRepository,
        private Security $security
    ) {}

    #[Route('', name: 'api_evaluation_detail_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des données
            if (!isset($data['evaluationId'])) {
                return $this->json([
                    'error' => 'Missing required field: evaluationId'
                ], 400);
            }

            // Récupérer l'évaluation
            $evaluation = $this->evaluationRepository->find($data['evaluationId']);
            if (!$evaluation) {
                return $this->json(['error' => 'Evaluation not found'], 404);
            }

            // Vérifier si des détails d'évaluation existent déjà
            $existingDetail = $this->evaluationDetailRepository->findByEvaluationId($evaluation->getId());

            if ($existingDetail) {
                // Mettre à jour les détails existants
                if (isset($data['competenceStatuses'])) {
                    $existingDetail->setCompetenceStatuses($data['competenceStatuses']);
                }
                if (isset($data['checkedSousCompetences'])) {
                    $existingDetail->setCheckedSousCompetences($data['checkedSousCompetences']);
                }
                if (isset($data['checkedActions'])) {
                    $existingDetail->setCheckedActions($data['checkedActions']);
                }
                if (isset($data['mainValue'])) {
                    $existingDetail->setMainValue($data['mainValue']);
                }
                if (isset($data['surfaceValue'])) {
                    $existingDetail->setSurfaceValue($data['surfaceValue']);
                }
                if (isset($data['originalMainValue'])) {
                    $existingDetail->setOriginalMainValue($data['originalMainValue']);
                }
                if (isset($data['originalSurfaceValue'])) {
                    $existingDetail->setOriginalSurfaceValue($data['originalSurfaceValue']);
                }

                $existingDetail->setUpdatedAt(new \DateTimeImmutable());

                $this->entityManager->flush();

                return $this->json([
                    'message' => 'Evaluation details updated successfully',
                    'evaluationDetail' => [
                        'id' => $existingDetail->getId(),
                        'evaluationId' => $evaluation->getId()
                    ]
                ], 200);
            }

            // Créer de nouveaux détails d'évaluation
            $evaluationDetail = new EvaluationDetail();
            $evaluationDetail->setEvaluation($evaluation);

            if (isset($data['competenceStatuses'])) {
                $evaluationDetail->setCompetenceStatuses($data['competenceStatuses']);
            }
            if (isset($data['checkedSousCompetences'])) {
                $evaluationDetail->setCheckedSousCompetences($data['checkedSousCompetences']);
            }
            if (isset($data['checkedActions'])) {
                $evaluationDetail->setCheckedActions($data['checkedActions']);
            }
            if (isset($data['mainValue'])) {
                $evaluationDetail->setMainValue($data['mainValue']);
            }
            if (isset($data['surfaceValue'])) {
                $evaluationDetail->setSurfaceValue($data['surfaceValue']);
            }
            if (isset($data['originalMainValue'])) {
                $evaluationDetail->setOriginalMainValue($data['originalMainValue']);
            }
            if (isset($data['originalSurfaceValue'])) {
                $evaluationDetail->setOriginalSurfaceValue($data['originalSurfaceValue']);
            }

            $this->entityManager->persist($evaluationDetail);
            $this->entityManager->flush();

            return $this->json([
                'message' => 'Evaluation details created successfully',
                'evaluationDetail' => [
                    'id' => $evaluationDetail->getId(),
                    'evaluationId' => $evaluation->getId()
                ]
            ], 201);

        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz/{quizId}/apprenant/{apprenantId}', name: 'api_evaluation_detail_by_quiz_apprenant', methods: ['GET'])]
    public function getByQuizAndApprenant(int $quizId, int $apprenantId): JsonResponse
    {
        try {
            // Vérifier si le quiz existe
            $quiz = $this->quizRepository->find($quizId);
            if (!$quiz) {
                return $this->json(['error' => 'Quiz not found'], 404);
            }

            // Vérifier si l'apprenant existe
            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Récupérer l'évaluation
            $evaluation = $this->evaluationRepository->findOneBy([
                'quiz' => $quiz,
                'apprenant' => $apprenant
            ]);

            if (!$evaluation) {
                return $this->json(['message' => 'No evaluation found'], 404);
            }

            // Récupérer les détails de l'évaluation
            $evaluationDetail = $this->evaluationDetailRepository->findByEvaluationId($evaluation->getId());

            if (!$evaluationDetail) {
                return $this->json(['message' => 'No evaluation details found'], 404);
            }

            return $this->json([
                'evaluationDetail' => [
                    'id' => $evaluationDetail->getId(),
                    'evaluationId' => $evaluation->getId(),
                    'competenceStatuses' => $evaluationDetail->getCompetenceStatuses(),
                    'checkedSousCompetences' => $evaluationDetail->getCheckedSousCompetences(),
                    'checkedActions' => $evaluationDetail->getCheckedActions(),
                    'mainValue' => $evaluationDetail->getMainValue(),
                    'surfaceValue' => $evaluationDetail->getSurfaceValue(),
                    'originalMainValue' => $evaluationDetail->getOriginalMainValue(),
                    'originalSurfaceValue' => $evaluationDetail->getOriginalSurfaceValue(),
                    'createdAt' => $evaluationDetail->getCreatedAt()->format('Y-m-d H:i:s'),
                    'updatedAt' => $evaluationDetail->getUpdatedAt() ? $evaluationDetail->getUpdatedAt()->format('Y-m-d H:i:s') : null
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
