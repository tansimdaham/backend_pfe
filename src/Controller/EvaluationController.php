<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Entity\Formateur;
use App\Entity\Quiz;
use App\Entity\Apprenant;
use App\Entity\Progression;
use App\Entity\Notification;
use App\Entity\Certificat;
use App\Repository\EvaluationRepository;
use App\Repository\QuizRepository;
use App\Repository\ApprenantRepository;
use App\Repository\FormateurRepository;
use App\Repository\ProgressionRepository;
use App\Repository\CertificatRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/evaluation')]
class EvaluationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EvaluationRepository $evaluationRepository,
        private QuizRepository $quizRepository,
        private ApprenantRepository $apprenantRepository,
        private FormateurRepository $formateurRepository,
        private ProgressionRepository $progressionRepository,
        private CertificatRepository $certificatRepository,
        private EmailService $emailService,
        private Security $security,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'api_evaluation_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $evaluations = $this->evaluationRepository->findAll();

            return $this->json([
                'evaluations' => $evaluations
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_evaluation_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $evaluation = $this->evaluationRepository->find($id);

            if (!$evaluation) {
                return $this->json(['error' => 'Evaluation not found'], 404);
            }

            return $this->json([
                'evaluation' => $evaluation
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_evaluation_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des données
            if (!isset($data['quizId']) || !isset($data['apprenantId']) || !isset($data['statut'])) {
                return $this->json([
                    'error' => 'Missing required fields',
                    'required' => ['quizId', 'apprenantId', 'statut']
                ], 400);
            }

            // Vérifier que le statut est valide
            if (!in_array($data['statut'], ['Satisfaisant', 'Non Satisfaisant'])) {
                return $this->json([
                    'error' => 'Invalid status',
                    'allowed' => ['Satisfaisant', 'Non Satisfaisant']
                ], 400);
            }

            // Récupérer le quiz
            $quiz = $this->quizRepository->find($data['quizId']);
            if (!$quiz) {
                return $this->json(['error' => 'Quiz not found'], 404);
            }

            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($data['apprenantId']);
            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Récupérer le formateur (utilisateur connecté)
            $user = $this->security->getUser();
            if (!$user || !($user instanceof Formateur)) {
                return $this->json(['error' => 'User must be a formateur'], 403);
            }

            // Vérifier si une évaluation existe déjà pour ce quiz et cet apprenant
            $existingEvaluation = $this->evaluationRepository->findOneBy([
                'quiz' => $quiz,
                'formateur' => $user,
                'apprenant' => $apprenant
            ]);

            if ($existingEvaluation) {
                // Mettre à jour l'évaluation existante
                $existingEvaluation->setStatutEvaluation($data['statut']);

                // S'assurer que idmodule est défini et synchronisé avec le quiz
                $existingEvaluation->synchronizeIdmodule();

                $this->entityManager->flush();

                return $this->json([
                    'message' => 'Evaluation updated successfully',
                    'evaluation' => [
                        'id' => $existingEvaluation->getId(),
                        'statut' => $existingEvaluation->getStatutEvaluation(),
                        'idmodule' => $existingEvaluation->getIdmodule(),
                        'quiz' => [
                            'id' => $quiz->getId(),
                            'nom' => $quiz->getNomFr()
                        ],
                        'formateur' => [
                            'id' => $existingEvaluation->getFormateur()->getId(),
                            'name' => $existingEvaluation->getFormateur()->getName()
                        ]
                    ]
                ], 200, [], ['circular_reference_handler' => function ($object) {
                    return $object->getId();
                }]);
            }

            // Créer une nouvelle évaluation
            $evaluation = new Evaluation();
            $evaluation->setStatutEvaluation($data['statut']);
            $evaluation->setQuiz($quiz); // Cela définira automatiquement idmodule grâce à notre setter modifié
            $evaluation->setFormateur($user);
            $evaluation->setApprenant($apprenant); // Associer l'apprenant à l'évaluation

            // S'assurer que idmodule est correctement défini et synchronisé avec le quiz
            $evaluation->synchronizeIdmodule();

            $this->entityManager->persist($evaluation);

            // Créer ou mettre à jour la progression
            $cours = $quiz->getCours();
            $progression = $this->progressionRepository->findOneBy([
                'cours' => $cours,
                'evaluation' => $evaluation,
                'apprenant' => $apprenant
            ]);

            if (!$progression) {
                $progression = new Progression();
                $progression->setCours($cours);
                $progression->setEvaluation($evaluation);
                $progression->setApprenant($apprenant); // Associer l'apprenant à la progression
                $progression->setTableEvaluations([
                    'quiz_id' => $quiz->getId(),
                    'statut' => $data['statut'],
                    'date' => (new \DateTime())->format('Y-m-d H:i:s')
                ]);

                $this->entityManager->persist($progression);
            } else {
                $tableEvaluations = $progression->getTableEvaluations();
                $tableEvaluations[] = [
                    'quiz_id' => $quiz->getId(),
                    'statut' => $data['statut'],
                    'date' => (new \DateTime())->format('Y-m-d H:i:s')
                ];
                $progression->setTableEvaluations($tableEvaluations);
            }

            // Créer une notification
            $notification = new Notification();
            $notification->setDescription("Vous avez reçu une évaluation " . $data['statut'] . " pour le quiz " . $quiz->getNomFr());
            $notification->setEvaluation($evaluation);
            $notification->setUser($apprenant->getUtilisateur());

            $this->entityManager->persist($notification);
            $this->entityManager->flush();

            // Vérifier si tous les quiz du cours sont "Satisfaisant" et générer un certificat si nécessaire
            $certificateInfo = null;
            if ($data['statut'] === 'Satisfaisant') {
                $certificateInfo = $this->checkAndGenerateCertificateIfNeeded($apprenant, $cours);
            }

            // Préparer une réponse structurée pour éviter les références circulaires
            $response = [
                'message' => 'Evaluation created successfully',
                'evaluation' => [
                    'id' => $evaluation->getId(),
                    'statut' => $evaluation->getStatutEvaluation(),
                    'idmodule' => $evaluation->getIdmodule(),
                    'quiz' => [
                        'id' => $quiz->getId(),
                        'nom' => $quiz->getNomFr()
                    ],
                    'formateur' => [
                        'id' => $evaluation->getFormateur()->getId(),
                        'name' => $evaluation->getFormateur()->getName()
                    ]
                ]
            ];

            // Ajouter les informations sur le certificat si un certificat a été généré ou existe déjà
            if ($certificateInfo) {
                $response['certificate'] = $certificateInfo;
            }

            return $this->json($response, 201, [], ['circular_reference_handler' => function ($object) {
                return $object->getId();
            }]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/idmodule/{idmodule}', name: 'api_evaluation_by_idmodule', methods: ['GET'])]
    public function getEvaluationsByIdmodule(string $idmodule): JsonResponse
    {
        try {
            $evaluations = $this->evaluationRepository->findByIdmodule($idmodule);

            if (empty($evaluations)) {
                return $this->json(['message' => 'No evaluations found for this idmodule'], 404);
            }

            // Préparer les données pour la réponse
            $evaluationsData = [];
            foreach ($evaluations as $evaluation) {
                $evaluationsData[] = [
                    'id' => $evaluation->getId(),
                    'statut' => $evaluation->getStatutEvaluation(),
                    'idmodule' => $evaluation->getIdmodule(),
                    'quiz' => [
                        'id' => $evaluation->getQuiz()->getId(),
                        'nom' => $evaluation->getQuiz()->getNomFr()
                    ],
                    'formateur' => [
                        'id' => $evaluation->getFormateur()->getId(),
                        'name' => $evaluation->getFormateur()->getName()
                    ]
                ];
            }

            return $this->json([
                'evaluations' => $evaluationsData
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/idmodule/{idmodule}/apprenant/{apprenantId}', name: 'api_evaluation_by_idmodule_apprenant', methods: ['GET'])]
    public function getEvaluationByIdmoduleAndApprenant(string $idmodule, int $apprenantId): JsonResponse
    {
        try {
            $evaluation = $this->evaluationRepository->findOneByIdmoduleAndApprenant($idmodule, $apprenantId);

            if (!$evaluation) {
                return $this->json(['message' => 'No evaluation found for this idmodule and apprenant'], 404);
            }

            return $this->json([
                'evaluation' => [
                    'id' => $evaluation->getId(),
                    'statut' => $evaluation->getStatutEvaluation(),
                    'idmodule' => $evaluation->getIdmodule(),
                    'quiz' => [
                        'id' => $evaluation->getQuiz()->getId(),
                        'nom' => $evaluation->getQuiz()->getNomFr()
                    ],
                    'formateur' => [
                        'id' => $evaluation->getFormateur()->getId(),
                        'name' => $evaluation->getFormateur()->getName()
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz/{quizId}/apprenant/{apprenantId}', name: 'api_evaluation_by_quiz_apprenant', methods: ['GET'])]
    public function getEvaluationByQuizAndApprenant(int $quizId, int $apprenantId): JsonResponse
    {
        try {
            $quiz = $this->quizRepository->find($quizId);
            if (!$quiz) {
                return $this->json(['error' => 'Quiz not found'], 404);
            }

            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Récupérer l'évaluation pour ce quiz et cet apprenant
            // Maintenant nous pouvons filtrer directement par apprenant grâce au nouvel attribut
            $evaluation = $this->evaluationRepository->findOneBy([
                'quiz' => $quiz,
                'apprenant' => $apprenant
            ]);

            if (!$evaluation) {
                return $this->json(['message' => 'No evaluation found'], 404);
            }

            return $this->json([
                'evaluation' => [
                    'id' => $evaluation->getId(),
                    'statut' => $evaluation->getStatutEvaluation(),
                    'idmodule' => $evaluation->getIdmodule(),
                    'quiz' => [
                        'id' => $quiz->getId(),
                        'nom' => $quiz->getNomFr()
                    ],
                    'formateur' => [
                        'id' => $evaluation->getFormateur()->getId(),
                        'name' => $evaluation->getFormateur()->getName()
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Vérifie si tous les quiz d'un cours sont marqués comme "Satisfaisant" et retourne les informations sur un certificat existant
     *
     * @param Apprenant $apprenant L'apprenant concerné
     * @param Cours $cours Le cours à vérifier
     * @return array Informations sur le certificat existant ou null si aucun certificat n'existe
     */
    private function checkAndGenerateCertificateIfNeeded(Apprenant $apprenant, $cours): ?array
    {
        error_log('DEBUG: Vérification de certificat existant - Apprenant ID: ' . $apprenant->getId() . ', Cours ID: ' . $cours->getId());

        // Vérifier si un certificat existe déjà pour cet apprenant et ce cours
        $existingCertificat = null;

        // Rechercher les certificats de l'apprenant
        $certificats = $this->certificatRepository->findBy([
            'apprenant' => $apprenant
        ]);

        // Vérifier si l'un des certificats correspond au cours actuel
        foreach ($certificats as $cert) {
            if ($cert->getProgression() && $cert->getProgression()->getCours() && $cert->getProgression()->getCours()->getId() === $cours->getId()) {
                $existingCertificat = $cert;
                break;
            }
        }

        if ($existingCertificat) {
            error_log('DEBUG: Un certificat existe déjà pour cet apprenant et ce cours - Certificat ID: ' . $existingCertificat->getId());
            return [
                'certificat_exists' => true,
                'certificat' => [
                    'id' => $existingCertificat->getId(),
                    'date_obtention' => $existingCertificat->getDateObtention()->format('Y-m-d')
                ]
            ];
        }

        error_log('DEBUG: Aucun certificat existant trouvé, vérification des quiz du cours');

        // Récupérer tous les quiz du cours
        $quizzes = $this->quizRepository->findBy(['cours' => $cours]);
        $totalQuizzes = count($quizzes);

        if ($totalQuizzes === 0) {
            return null; // Pas de quiz dans ce cours
        }

        $passedQuizzes = 0;

        // Vérifier chaque quiz
        foreach ($quizzes as $quiz) {
            $evaluation = $this->evaluationRepository->findOneBy([
                'quiz' => $quiz,
                'apprenant' => $apprenant
            ]);

            if ($evaluation && $evaluation->getStatutEvaluation() === 'Satisfaisant') {
                $passedQuizzes++;
            }
        }

        // Calculer le pourcentage de réussite
        $successRate = ($passedQuizzes / $totalQuizzes) * 100;
        error_log(sprintf('DEBUG: Calcul du taux de réussite - Total: %d, Réussis: %d, Taux: %.2f%%',
            $totalQuizzes, $passedQuizzes, $successRate));

        // Si tous les quiz sont "Satisfaisant", mettre à jour la progression mais ne pas générer de certificat
        if ($successRate === 100.0) {
            error_log('DEBUG: Taux de réussite à 100%, mise à jour de la progression');
            // Récupérer ou créer une progression
            $progression = $this->progressionRepository->findOneBy([
                'cours' => $cours,
                'apprenant' => $apprenant
            ]);

            if (!$progression) {
                $progression = new Progression();
                $progression->setCours($cours);
                $progression->setApprenant($apprenant); // Associer l'apprenant à la progression

                // Récupérer la dernière évaluation pour ce cours et cet apprenant
                $latestEvaluation = $this->evaluationRepository->findOneBy(
                    [
                        'quiz' => $quizzes[0],
                        'apprenant' => $apprenant
                    ],
                    ['id' => 'DESC']
                );

                if ($latestEvaluation) {
                    $progression->setEvaluation($latestEvaluation);
                }

                $progression->setTableEvaluations([
                    'success_rate' => $successRate,
                    'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'quizzes_total' => $totalQuizzes,
                    'quizzes_passed' => $passedQuizzes
                ]);

                $this->entityManager->persist($progression);
                $this->entityManager->flush();
            } else {
                // Mettre à jour les informations de progression
                $tableEvaluations = $progression->getTableEvaluations();
                $tableEvaluations['success_rate'] = $successRate;
                $tableEvaluations['date'] = (new \DateTime())->format('Y-m-d H:i:s');
                $tableEvaluations['quizzes_total'] = $totalQuizzes;
                $tableEvaluations['quizzes_passed'] = $passedQuizzes;
                $progression->setTableEvaluations($tableEvaluations);
                $this->entityManager->flush();
            }

            // Retourner un message indiquant que le cours est complété mais qu'aucun certificat n'a été généré automatiquement
            return [
                'course_completed' => true,
                'message' => 'Le cours est complété à 100%. Utilisez le bouton pour générer un certificat manuellement.',
                'success_rate' => $successRate
            ];
        }

        return null; // Pas de certificat généré
    }
}
