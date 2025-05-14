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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/certificat')]
class CertificatController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CertificatRepository $certificatRepository,
        private ApprenantRepository $apprenantRepository,
        private CoursRepository $coursRepository,
        private ProgressionRepository $progressionRepository,
        private EvaluationRepository $evaluationRepository,
        private QuizRepository $quizRepository,
        private Security $security,
        private SerializerInterface $serializer,
        private \App\Service\EmailService $emailService
    ) {}

    #[Route('', name: 'api_certificat_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $certificats = $this->certificatRepository->findAll();

            return $this->json([
                'certificats' => $certificats
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/check-and-generate/{apprenantId}/{coursId}', name: 'api_certificat_check_and_generate', methods: ['GET'])]
    public function checkAndGenerate(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            error_log("DEBUG: Vérification du certificat - Apprenant: $apprenantId, Cours: $coursId");

            // Récupérer l'apprenant et le cours
            $apprenant = $this->apprenantRepository->find($apprenantId);
            $cours = $this->coursRepository->find($coursId);

            if (!$apprenant || !$cours) {
                error_log("DEBUG: Apprenant ou cours non trouvé");
                return $this->json(['error' => 'Apprenant or Cours not found'], 404);
            }

            // Vérifier la progression
            $progression = $this->progressionRepository->findOneBy([
                'apprenant' => $apprenant,
                'cours' => $cours
            ]);

            // Vérifier si un certificat existe déjà
            $existingCertificat = null;
            if ($progression) {
                $existingCertificat = $this->certificatRepository->findOneBy([
                    'apprenant' => $apprenant,
                    'progression' => $progression
                ]);
            }

            if ($existingCertificat) {
                error_log("DEBUG: Certificat existant trouvé - ID: " . $existingCertificat->getId());
                return $this->json([
                    'message' => 'Certificate already exists',
                    'certificat' => [
                        'id' => $existingCertificat->getId(),
                        'apprenant_id' => $existingCertificat->getApprenant()->getId(),
                        'date_obtention' => $existingCertificat->getDateObtention()->format('Y-m-d'),
                        'contenu' => $existingCertificat->getContenu()
                    ]
                ], 200);
            }

            // Retourner un message indiquant que la génération automatique est désactivée
            return $this->json([
                'message' => 'Automatic certificate generation is disabled. Please use the button to generate a certificate manually.',
                'success_rate' => $progression ? $progression->getPourcentage() : 0,
                'required_rate' => 100
            ], 200);

        } catch (\Exception $e) {
            error_log('ERREUR dans check-and-generate: ' . $e->getMessage());
            return $this->json([
                'error' => 'Error checking certificate',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_certificat_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $certificat = $this->certificatRepository->find($id);

            if (!$certificat) {
                return $this->json(['error' => 'Certificat not found'], 404);
            }

            return $this->json([
                'certificat' => $certificat
            ], 200);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



    #[Route('/generate-direct', name: 'api_certificat_generate_direct', methods: ['POST'])]
    public function generateDirect(Request $request): JsonResponse
    {
        try {
            error_log("Début de la méthode generateDirect");
            $data = json_decode($request->getContent(), true);
            error_log("Données reçues: " . print_r($data, true));

            // Validation des données
            if (!isset($data['apprenantId']) || !isset($data['coursId'])) {
                error_log("Données manquantes: apprenantId ou coursId");
                return $this->json([
                    'error' => 'Missing required fields',
                    'required' => ['apprenantId', 'coursId']
                ], 400);
            }

            error_log("Validation des données réussie");

            $apprenantId = $data['apprenantId'];
            $coursId = $data['coursId'];

            // Récupérer l'apprenant et le cours pour les informations
            $apprenant = $this->apprenantRepository->find($apprenantId);
            $cours = $this->coursRepository->find($coursId);

            if (!$apprenant || !$cours) {
                error_log("Apprenant ou cours non trouvé - Apprenant ID: $apprenantId, Cours ID: $coursId");
                return $this->json([
                    'error' => 'Apprenant or Cours not found',
                    'apprenant_id' => $apprenantId,
                    'cours_id' => $coursId
                ], 404);
            }

            // Vérifier que l'apprenant existe bien dans la base de données
            try {
                $checkApprenantSql = 'SELECT id FROM apprenant WHERE id = :id';
                $checkApprenantStmt = $connection->prepare($checkApprenantSql);
                $checkApprenantResult = $checkApprenantStmt->executeQuery(['id' => $apprenantId]);
                $apprenantExists = $checkApprenantResult->fetchAssociative();

                if (!$apprenantExists) {
                    error_log("L'apprenant avec ID $apprenantId n'existe pas dans la base de données");
                    return $this->json([
                        'error' => 'Apprenant not found in database',
                        'message' => 'L\'apprenant spécifié n\'existe pas dans la base de données.'
                    ], 404);
                }

                error_log("Apprenant vérifié avec succès, ID: $apprenantId");
            } catch (\Exception $e) {
                error_log("Erreur lors de la vérification de l'apprenant: " . $e->getMessage());
                // Continuer quand même, car l'objet Apprenant a été trouvé via le repository
            }

            // Vérifier si un certificat existe déjà
            $connection = $this->entityManager->getConnection();
            error_log("Vérification de l'existence d'un certificat pour l'apprenant $apprenantId et le cours $coursId");

            // Recherche par progression et par apprenant_id directement
            $sql = 'SELECT c.id, c.date_obtention, c.contenu
                    FROM certificat c
                    LEFT JOIN progression p ON c.progression_id = p.id
                    WHERE (p.apprenant_id = :apprenantId AND p.cours_id = :coursId)
                       OR (c.apprenant_id = :apprenantId AND EXISTS (
                           SELECT 1 FROM progression p2
                           WHERE p2.id = c.progression_id AND p2.cours_id = :coursId
                       ))
                    LIMIT 1';

            // Requête alternative plus simple si la première échoue
            $backupSql = 'SELECT c.id, c.date_obtention, c.contenu
                         FROM certificat c
                         JOIN progression p ON c.progression_id = p.id
                         WHERE p.cours_id = :coursId AND c.apprenant_id = :apprenantId
                         LIMIT 1';

            $existingCertificat = null;

            try {
                $stmt = $connection->prepare($sql);
                $resultSet = $stmt->executeQuery([
                    'apprenantId' => $apprenantId,
                    'coursId' => $coursId
                ]);

                error_log("Requête de vérification de certificat exécutée");
                $existingCertificat = $resultSet->fetchAssociative();
            } catch (\Exception $e) {
                error_log("Erreur lors de la vérification du certificat: " . $e->getMessage());
                error_log("Tentative avec la requête alternative...");

                try {
                    // Essayer la requête alternative
                    $backupStmt = $connection->prepare($backupSql);
                    $backupResultSet = $backupStmt->executeQuery([
                        'apprenantId' => $apprenantId,
                        'coursId' => $coursId
                    ]);

                    $existingCertificat = $backupResultSet->fetchAssociative();
                    error_log("Requête alternative exécutée avec succès");
                } catch (\Exception $e2) {
                    error_log("Erreur lors de la requête alternative: " . $e2->getMessage());
                    // Continuer sans certificat existant
                }
            }

            if ($existingCertificat) {
                // Retourner le certificat existant
                $contenuData = json_decode($existingCertificat['contenu'], true);

                return $this->json([
                    'message' => 'Certificate already exists',
                    'certificat' => [
                        'id' => $existingCertificat['id'],
                        'date_obtention' => $existingCertificat['date_obtention'],
                        'apprenant' => [
                            'id' => $apprenant->getId(),
                            'name' => $apprenant->getName(),
                            'email' => $apprenant->getEmail()
                        ],
                        'cours' => [
                            'id' => $cours->getId(),
                            'titre' => $cours->getTitre(),
                            'description' => $cours->getDescription()
                        ],
                        'competences' => $contenuData['competences'] ?? [],
                        'numero_certificat' => sprintf('CERT-%06d', $existingCertificat['id']),
                        'contenu' => $existingCertificat['contenu']
                    ]
                ], 200);
            }

            // Récupérer ou créer une progression
            error_log("Recherche d'une progression existante pour l'apprenant $apprenantId et le cours $coursId");
            $sql = 'SELECT id, evaluation_id FROM progression WHERE apprenant_id = :apprenantId AND cours_id = :coursId LIMIT 1';
            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery([
                'apprenantId' => $apprenantId,
                'coursId' => $coursId
            ]);

            error_log("Requête de recherche de progression exécutée");

            $progressionData = $resultSet->fetchAssociative();
            $progressionId = null;

            if ($progressionData) {
                $progressionId = $progressionData['id'];

                // Vérifier si la progression a une évaluation
                $checkEvalSql = 'SELECT evaluation_id FROM progression WHERE id = :id';
                $checkEvalStmt = $connection->prepare($checkEvalSql);
                $checkEvalResult = $checkEvalStmt->executeQuery(['id' => $progressionId]);
                $progressionEval = $checkEvalResult->fetchAssociative();

                if (!$progressionEval || !$progressionEval['evaluation_id']) {
                    error_log("La progression existe mais n'a pas d'évaluation, recherche d'une évaluation...");

                    // Trouver une évaluation existante pour ce cours
                    $evaluationId = null;

                    // Récupérer les quizzes du cours
                    $quizzes = $this->quizRepository->findBy(['cours' => $cours]);

                    if (!empty($quizzes)) {
                        // Chercher une évaluation pour l'un des quizzes
                        foreach ($quizzes as $quiz) {
                            $evalSql = 'SELECT id FROM evaluation WHERE quiz_id = :quizId LIMIT 1';
                            $evalStmt = $connection->prepare($evalSql);
                            $evalResult = $evalStmt->executeQuery(['quizId' => $quiz->getId()]);
                            $evaluation = $evalResult->fetchAssociative();

                            if ($evaluation && isset($evaluation['id'])) {
                                $evaluationId = $evaluation['id'];
                                error_log("Évaluation trouvée avec ID: " . $evaluationId);
                                break;
                            }
                        }
                    }

                    // Si aucune évaluation n'est trouvée, en créer une par défaut
                    if (!$evaluationId && !empty($quizzes)) {
                        $firstQuiz = $quizzes[0];
                        $evalInsertSql = 'INSERT INTO evaluation (statut_evaluation, formateur_id, quiz_id, idmodule, apprenant_id)
                                         VALUES (:statut, :formateurId, :quizId, :idmodule, :apprenantId)';
                        $evalInsertStmt = $connection->prepare($evalInsertSql);

                        // Trouver un formateur (prendre le premier disponible)
                        $formateurSql = 'SELECT id FROM formateur LIMIT 1';
                        $formateurStmt = $connection->prepare($formateurSql);
                        $formateurResult = $formateurStmt->executeQuery();
                        $formateur = $formateurResult->fetchAssociative();
                        $formateurId = $formateur ? $formateur['id'] : 1; // Utiliser l'ID 1 par défaut si aucun formateur n'est trouvé

                        $evalInsertStmt->executeStatement([
                            'statut' => 'Satisfaisant',
                            'formateurId' => $formateurId,
                            'quizId' => $firstQuiz->getId(),
                            'idmodule' => $firstQuiz->getIDModule() ?: 'default',
                            'apprenantId' => $apprenantId
                        ]);

                        $evaluationId = $connection->lastInsertId();
                        error_log("Nouvelle évaluation créée avec ID: " . $evaluationId);
                    }

                    // Mettre à jour la progression avec l'évaluation
                    if ($evaluationId) {
                        $updateSql = 'UPDATE progression SET evaluation_id = :evaluationId WHERE id = :id';
                        $updateStmt = $connection->prepare($updateSql);
                        $updateStmt->executeStatement([
                            'evaluationId' => $evaluationId,
                            'id' => $progressionId
                        ]);
                        error_log("Progression mise à jour avec evaluation_id: " . $evaluationId);
                    } else {
                        // Si nous ne pouvons pas créer d'évaluation, retourner une erreur
                        error_log("Impossible de mettre à jour la progression sans évaluation valide");
                        return $this->json([
                            'error' => 'Evaluation not found',
                            'message' => 'Aucune évaluation n\'a été trouvée pour ce cours et impossible d\'en créer une. Impossible de générer un certificat.'
                        ], 400);
                    }
                }
            } else {
                // Trouver une évaluation existante pour ce cours
                $evaluationId = null;

                // Récupérer les quizzes du cours
                $quizzes = $this->quizRepository->findBy(['cours' => $cours]);

                if (!empty($quizzes)) {
                    // Chercher une évaluation pour l'un des quizzes
                    foreach ($quizzes as $quiz) {
                        $evalSql = 'SELECT id FROM evaluation WHERE quiz_id = :quizId LIMIT 1';
                        $evalStmt = $connection->prepare($evalSql);
                        $evalResult = $evalStmt->executeQuery(['quizId' => $quiz->getId()]);
                        $evaluation = $evalResult->fetchAssociative();

                        if ($evaluation && isset($evaluation['id'])) {
                            $evaluationId = $evaluation['id'];
                            error_log("Évaluation trouvée avec ID: " . $evaluationId);
                            break;
                        }
                    }
                }

                // Si aucune évaluation n'est trouvée, en créer une par défaut
                if (!$evaluationId && !empty($quizzes)) {
                    $firstQuiz = $quizzes[0];
                    $evalInsertSql = 'INSERT INTO evaluation (statut_evaluation, formateur_id, quiz_id, idmodule, apprenant_id)
                                     VALUES (:statut, :formateurId, :quizId, :idmodule, :apprenantId)';
                    $evalInsertStmt = $connection->prepare($evalInsertSql);

                    // Trouver un formateur (prendre le premier disponible)
                    $formateurSql = 'SELECT id FROM formateur LIMIT 1';
                    $formateurStmt = $connection->prepare($formateurSql);
                    $formateurResult = $formateurStmt->executeQuery();
                    $formateur = $formateurResult->fetchAssociative();
                    $formateurId = $formateur ? $formateur['id'] : 1; // Utiliser l'ID 1 par défaut si aucun formateur n'est trouvé

                    $evalInsertStmt->executeStatement([
                        'statut' => 'Satisfaisant',
                        'formateurId' => $formateurId,
                        'quizId' => $firstQuiz->getId(),
                        'idmodule' => $firstQuiz->getIDModule() ?: 'default',
                        'apprenantId' => $apprenantId
                    ]);

                    $evaluationId = $connection->lastInsertId();
                    error_log("Nouvelle évaluation créée avec ID: " . $evaluationId);
                }

                // Créer une nouvelle progression avec l'évaluation
                if ($evaluationId) {
                    $sql = 'INSERT INTO progression (apprenant_id, cours_id, evaluation_id, table_evaluations)
                            VALUES (:apprenantId, :coursId, :evaluationId, :tableEvaluations)';
                    $stmt = $connection->prepare($sql);
                    $stmt->executeStatement([
                        'apprenantId' => $apprenantId,
                        'coursId' => $coursId,
                        'evaluationId' => $evaluationId,
                        'tableEvaluations' => json_encode([
                            'success_rate' => 100,
                            'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                            'quizzes_total' => 1,
                            'quizzes_passed' => 1
                        ])
                    ]);

                    $progressionId = $connection->lastInsertId();
                    error_log("Nouvelle progression créée avec ID: " . $progressionId . " et evaluation_id: " . $evaluationId);
                } else {
                    // Si nous ne pouvons pas créer d'évaluation, retourner une erreur
                    error_log("Impossible de créer une progression sans évaluation valide");
                    return $this->json([
                        'error' => 'Evaluation not found',
                        'message' => 'Aucune évaluation n\'a été trouvée pour ce cours et impossible d\'en créer une. Impossible de générer un certificat.'
                    ], 400);
                }
            }

            // Récupérer les compétences (quizzes) du cours
            $quizzes = $this->quizRepository->findBy(['cours' => $cours]);
            $competences = [];

            foreach ($quizzes as $quiz) {
                $competences[] = $quiz->getNomFR() ?? 'Compétence ' . $quiz->getIDModule();
            }

            // Générer le contenu du certificat
            $contenuData = [
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName(),
                    'email' => $apprenant->getEmail()
                ],
                'cours' => [
                    'id' => $cours->getId(),
                    'titre' => $cours->getTitre(),
                    'description' => $cours->getDescription()
                ],
                'date_obtention' => (new \DateTime())->format('Y-m-d'),
                'competences' => $competences
            ];

            $contenuCertificat = json_encode($contenuData);

            // Vérifier que la progression existe bien
            error_log("Vérification de l'existence de la progression avec ID: " . $progressionId);
            $checkProgressionSql = 'SELECT id FROM progression WHERE id = :id';
            $checkProgressionStmt = $connection->prepare($checkProgressionSql);
            $checkProgressionResult = $checkProgressionStmt->executeQuery(['id' => $progressionId]);
            $progressionExists = $checkProgressionResult->fetchAssociative();

            if (!$progressionExists) {
                error_log("ERREUR: La progression avec ID " . $progressionId . " n'existe pas");
                return $this->json([
                    'error' => 'Progression not found',
                    'message' => 'La progression nécessaire pour créer le certificat n\'existe pas.'
                ], 500);
            }

            // Vérifier que la progression a bien une évaluation
            $checkEvalSql = 'SELECT evaluation_id FROM progression WHERE id = :id';
            $checkEvalStmt = $connection->prepare($checkEvalSql);
            $checkEvalResult = $checkEvalStmt->executeQuery(['id' => $progressionId]);
            $progressionEval = $checkEvalResult->fetchAssociative();

            if (!$progressionEval || !$progressionEval['evaluation_id']) {
                error_log("ERREUR: La progression n'a pas d'évaluation associée");
                return $this->json([
                    'error' => 'Evaluation not found',
                    'message' => 'La progression n\'a pas d\'évaluation associée. Impossible de générer un certificat.'
                ], 500);
            }

            error_log("Progression validée, création du certificat...");

            // Insérer le certificat directement en SQL avec apprenant_id
            try {
                error_log("Création du certificat avec apprenant_id: " . $apprenant->getId());

                $sql = 'INSERT INTO certificat (date_obtention, contenu, progression_id, apprenant_id, is_auto_generated)
                        VALUES (:dateObtention, :contenu, :progressionId, :apprenantId, :isAutoGenerated)';
                $stmt = $connection->prepare($sql);
                $stmt->executeStatement([
                    'dateObtention' => (new \DateTime())->format('Y-m-d'),
                    'contenu' => $contenuCertificat,
                    'progressionId' => $progressionId,
                    'apprenantId' => $apprenant->getId(),
                    'isAutoGenerated' => true
                ]);

                $certificatId = $connection->lastInsertId();
                error_log("Certificat créé avec succès, ID: " . $certificatId);
            } catch (\Exception $e) {
                error_log("ERREUR lors de la création du certificat: " . $e->getMessage());

                // Vérifier si l'erreur est liée à la contrainte de clé étrangère apprenant_id
                if (strpos($e->getMessage(), 'FK_27448F77C5697D6D') !== false) {
                    error_log("Erreur de contrainte de clé étrangère sur apprenant_id");

                    // Vérifier que l'apprenant existe bien
                    $checkApprenantSql = 'SELECT id FROM apprenant WHERE id = :id';
                    $checkApprenantStmt = $connection->prepare($checkApprenantSql);
                    $checkApprenantResult = $checkApprenantStmt->executeQuery(['id' => $apprenant->getId()]);
                    $apprenantExists = $checkApprenantResult->fetchAssociative();

                    if (!$apprenantExists) {
                        error_log("L'apprenant avec ID " . $apprenant->getId() . " n'existe pas dans la base de données");
                        return $this->json([
                            'error' => 'Apprenant not found',
                            'message' => 'L\'apprenant spécifié n\'existe pas dans la base de données.'
                        ], 404);
                    }
                }

                return $this->json([
                    'error' => 'Certificate creation failed',
                    'message' => 'Erreur lors de la création du certificat: ' . $e->getMessage()
                ], 500);
            }

            // Créer une notification
            try {
                $utilisateurId = $apprenant->getUtilisateur()->getId();
                error_log("Création d'une notification pour l'utilisateur ID: " . $utilisateurId);

                $sql = 'INSERT INTO notification (description, user_id, certificat_id)
                        VALUES (:description, :userId, :certificatId)';
                $stmt = $connection->prepare($sql);
                $stmt->executeStatement([
                    'description' => "Félicitations ! Vous avez obtenu un certificat pour le cours " . $cours->getTitre(),
                    'userId' => $utilisateurId,
                    'certificatId' => $certificatId
                ]);

                error_log("Notification créée avec succès");
            } catch (\Exception $e) {
                // Ne pas bloquer le processus si la notification échoue
                error_log("AVERTISSEMENT: Erreur lors de la création de la notification: " . $e->getMessage());
                // Continuer sans notification
            }

            // Retourner les données du certificat
            return $this->json([
                'message' => 'Certificate generated successfully',
                'certificat' => [
                    'id' => (int)$certificatId,
                    'date_obtention' => (new \DateTime())->format('Y-m-d'),
                    'isAutoGenerated' => true,
                    'apprenant' => [
                        'id' => $apprenant->getId(),
                        'name' => $apprenant->getName(),
                        'email' => $apprenant->getEmail()
                    ],
                    'cours' => [
                        'id' => $cours->getId(),
                        'titre' => $cours->getTitre(),
                        'description' => $cours->getDescription()
                    ],
                    'competences' => $competences,
                    'numero_certificat' => sprintf('CERT-%06d', $certificatId),
                    'contenu' => $contenuCertificat
                ]
            ], 201);
        } catch (\Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException $e) {
            error_log("ERREUR de contrainte de clé étrangère: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            return $this->json([
                'error' => 'Foreign key constraint violation',
                'message' => 'Une erreur de contrainte de clé étrangère est survenue. Vérifiez que toutes les relations sont correctement établies.',
                'details' => $e->getMessage()
            ], 500);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            error_log("ERREUR de contrainte d'unicité: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            return $this->json([
                'error' => 'Unique constraint violation',
                'message' => 'Un certificat existe probablement déjà pour ce cours et cet apprenant.',
                'details' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            error_log("ERREUR générale lors de la génération du certificat: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            error_log("Type d'exception: " . get_class($e));

            return $this->json([
                'error' => 'Error generating certificate',
                'message' => 'Une erreur est survenue lors de la génération du certificat: ' . $e->getMessage(),
                'exception_type' => get_class($e)
            ], 500);
        }
    }

    #[Route('/generate', name: 'api_certificat_generate', methods: ['POST'])]
    public function generate(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des données
            if (!isset($data['apprenantId']) || !isset($data['coursId'])) {
                return $this->json([
                    'error' => 'Missing required fields',
                    'required' => ['apprenantId', 'coursId']
                ], 400);
            }

            // Récupérer l'apprenant
            $apprenant = $this->apprenantRepository->find($data['apprenantId']);
            if (!$apprenant) {
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Récupérer le cours
            $cours = $this->coursRepository->find($data['coursId']);
            if (!$cours) {
                return $this->json(['error' => 'Cours not found'], 404);
            }

            // Récupérer l'utilisateur connecté
            $currentUser = $this->security->getUser();

            // Vérifier si l'utilisateur est un formateur ou si l'apprenant est inscrit à ce cours
            $isFormateur = $currentUser && in_array('ROLE_FORMATEUR', $currentUser->getRoles());

            if (!$isFormateur && !$apprenant->getCours()->contains($cours)) {
                return $this->json(['error' => 'Apprenant is not enrolled in this course or you do not have permission to generate a certificate'], 403);
            }

            // Vérifier si l'apprenant a complété tous les quiz du cours avec succès
            $quizzes = $this->quizRepository->findBy(['cours' => $cours]);
            $totalQuizzes = count($quizzes);
            $passedQuizzes = 0;

            foreach ($quizzes as $quiz) {
                $evaluation = $this->evaluationRepository->findOneBy(['quiz' => $quiz]);

                if ($evaluation && $evaluation->getStatutEvaluation() === 'Satisfaisant') {
                    $passedQuizzes++;
                }
            }

            // Calculer le pourcentage de réussite
            $successRate = $totalQuizzes > 0 ? ($passedQuizzes / $totalQuizzes) * 100 : 0;

            // Vérifier si le taux de réussite est de 100%
            if ($successRate < 100) {
                return $this->json([
                    'error' => 'Cannot generate certificate',
                    'message' => 'Apprenant has not completed all quizzes successfully',
                    'success_rate' => $successRate,
                    'required_rate' => 100
                ], 400);
            }

            // Vérifier si un certificat existe déjà pour ce cours et cet apprenant
            $existingCertificat = null;

            // Log pour le débogage
            error_log("Vérification de l'existence d'un certificat pour l'apprenant {$apprenant->getId()} et le cours {$cours->getId()}");

            try {
                // Récupérer les certificats de l'apprenant
                $certificats = $this->certificatRepository->findBy([
                    'apprenant' => $apprenant
                ]);

                error_log("Nombre de certificats trouvés pour l'apprenant: " . count($certificats));

                // Vérifier si l'un des certificats correspond au cours actuel
                foreach ($certificats as $cert) {
                    if ($cert->getProgression() && $cert->getProgression()->getCours() && $cert->getProgression()->getCours()->getId() === $cours->getId()) {
                        $existingCertificat = $cert;
                        error_log("Certificat existant trouvé avec ID: " . $cert->getId());
                        break;
                    }
                }
            } catch (\Exception $e) {
                error_log("Erreur lors de la recherche des certificats existants: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());

                // Continuer sans certificat existant
                $certificats = [];
            }

            if ($existingCertificat) {
                // Récupérer les compétences acquises
                $competences = [];

                foreach ($quizzes as $quiz) {
                    $evaluation = $this->evaluationRepository->findOneBy(['quiz' => $quiz]);
                    if ($evaluation && $evaluation->getStatutEvaluation() === 'Satisfaisant') {
                        // Ajouter les compétences du quiz
                        $competences[] = $quiz->getNomFR() ?? 'Compétence ' . $quiz->getIDModule();
                    }
                }

                return $this->json([
                    'message' => 'Certificate already exists',
                    'certificat' => [
                        'id' => $existingCertificat->getId(),
                        'date_obtention' => $existingCertificat->getDateObtention()->format('Y-m-d'),
                        'apprenant' => [
                            'id' => $apprenant->getId(),
                            'name' => $apprenant->getName(),
                            'email' => $apprenant->getEmail()
                        ],
                        'cours' => [
                            'id' => $cours->getId(),
                            'titre' => $cours->getTitre(),
                            'description' => $cours->getDescription()
                        ],
                        'competences' => $competences,
                        'numero_certificat' => sprintf('CERT-%06d', $existingCertificat->getId())
                    ]
                ], 200);
            }

            // Récupérer la progression pour cet apprenant et ce cours
            $progression = null;

            // Recherche de progression existante
            try {
                // Utiliser une requête SQL directe pour éviter les problèmes d'ORM
                $connection = $this->entityManager->getConnection();
                $sql = 'SELECT p.id FROM progression p
                        WHERE p.cours_id = :coursId
                        AND p.apprenant_id = :apprenantId
                        LIMIT 1';

                $stmt = $connection->prepare($sql);
                $resultSet = $stmt->executeQuery([
                    'coursId' => $cours->getId(),
                    'apprenantId' => $apprenant->getId()
                ]);

                $result = $resultSet->fetchAssociative();

                if ($result && isset($result['id'])) {
                    $progressionId = $result['id'];
                    $progression = $this->progressionRepository->find($progressionId);
                    error_log("Progression existante trouvée avec ID: " . $progressionId);
                } else {
                    error_log("Aucune progression trouvée pour l'apprenant {$apprenant->getId()} et le cours {$cours->getId()}");
                }
            } catch (\Exception $e) {
                error_log("Erreur lors de la recherche de progression: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());
            }

            // Si toujours pas de progression, en créer une nouvelle
            if (!$progression) {
                error_log("Création d'une nouvelle progression pour l'apprenant {$apprenant->getId()} et le cours {$cours->getId()}");

                // Créer une nouvelle progression
                $progression = new Progression();
                $progression->setCours($cours);
                $progression->setApprenant($apprenant);

                // Récupérer la dernière évaluation pour ce cours
                $latestEvaluation = null;
                foreach ($quizzes as $quiz) {
                    try {
                        $evaluation = $this->evaluationRepository->findOneBy(
                            ['quiz' => $quiz],
                            ['id' => 'DESC']
                        );
                        if ($evaluation) {
                            $latestEvaluation = $evaluation;
                            error_log("Évaluation trouvée pour le quiz {$quiz->getId()}, ID: {$evaluation->getId()}");
                            break;
                        }
                    } catch (\Exception $e) {
                        error_log("Erreur lors de la recherche d'évaluation pour le quiz {$quiz->getId()}: " . $e->getMessage());
                    }
                }

                if ($latestEvaluation) {
                    $progression->setEvaluation($latestEvaluation);
                    error_log("Évaluation associée à la progression, ID: " . $latestEvaluation->getId());
                } else {
                    // Log l'erreur
                    error_log('Aucune évaluation trouvée pour le cours ' . $cours->getId());
                    return $this->json([
                        'error' => 'Evaluation not found',
                        'message' => 'Aucune évaluation n\'a été trouvée pour ce cours. Impossible de générer un certificat.'
                    ], 400);
                }

                $progression->setTableEvaluations([
                    'success_rate' => $successRate,
                    'date' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'quizzes_total' => $totalQuizzes,
                    'quizzes_passed' => $passedQuizzes
                ]);

                // Persister et flusher immédiatement pour obtenir un ID
                $this->entityManager->persist($progression);
                $this->entityManager->flush();
                error_log("Nouvelle progression créée avec ID: " . $progression->getId());
            } else if (!$progression->getEvaluation()) {
                // Si la progression existe mais n'a pas d'évaluation, en ajouter une
                error_log("La progression existe mais n'a pas d'évaluation, recherche d'une évaluation...");

                $latestEvaluation = null;
                foreach ($quizzes as $quiz) {
                    try {
                        $evaluation = $this->evaluationRepository->findOneBy(
                            ['quiz' => $quiz],
                            ['id' => 'DESC']
                        );
                        if ($evaluation) {
                            $latestEvaluation = $evaluation;
                            error_log("Évaluation trouvée pour le quiz {$quiz->getId()}, ID: {$evaluation->getId()}");
                            break;
                        }
                    } catch (\Exception $e) {
                        error_log("Erreur lors de la recherche d'évaluation pour le quiz {$quiz->getId()}: " . $e->getMessage());
                    }
                }

                if ($latestEvaluation) {
                    $progression->setEvaluation($latestEvaluation);
                    error_log("Évaluation associée à la progression existante, ID: " . $latestEvaluation->getId());
                    $this->entityManager->flush(); // Flush pour sauvegarder la mise à jour
                } else {
                    // Log l'erreur
                    error_log('Aucune évaluation trouvée pour le cours ' . $cours->getId());
                    return $this->json([
                        'error' => 'Evaluation not found',
                        'message' => 'Aucune évaluation n\'a été trouvée pour ce cours. Impossible de générer un certificat.'
                    ], 400);
                }
            }

            // Créer un nouveau certificat
            $certificat = new Certificat();
            $certificat->setDateObtention(new \DateTime());

            // Récupérer les compétences acquises pour le contenu du certificat
            $competences = [];
            foreach ($quizzes as $quiz) {
                $evaluation = $this->evaluationRepository->findOneBy(['quiz' => $quiz]);
                if ($evaluation && $evaluation->getStatutEvaluation() === 'Satisfaisant') {
                    // Ajouter les compétences du quiz
                    $competences[] = $quiz->getNomFR() ?? 'Compétence ' . $quiz->getIDModule();
                }
            }

            // Générer le contenu du certificat au format JSON
            $contenuData = [
                'apprenant' => [
                    'id' => $apprenant->getId(),
                    'name' => $apprenant->getName(),
                    'email' => $apprenant->getEmail()
                ],
                'cours' => [
                    'id' => $cours->getId(),
                    'titre' => $cours->getTitre(),
                    'description' => $cours->getDescription()
                ],
                'date_obtention' => (new \DateTime())->format('Y-m-d'),
                'competences' => $competences
            ];

            // Log pour le débogage
            error_log('Contenu du certificat à enregistrer: ' . print_r($contenuData, true));

            $contenuCertificat = json_encode($contenuData);

            // Enregistrer le contenu du certificat
            $certificat->setContenu($contenuCertificat);

            // Log pour le débogage
            error_log('Contenu du certificat après json_encode: ' . $contenuCertificat);

            // Définir l'apprenant et la progression
            // Nous avons modifié la relation pour que Certificat soit le propriétaire
            // et qu'il n'y ait plus de relation bidirectionnelle
            $certificat->setApprenant($apprenant);

            // S'assurer que la progression est persistée et a un ID
            if (!$progression->getId()) {
                error_log("ATTENTION: La progression n'a pas d'ID avant d'être associée au certificat");
                // Forcer un flush pour obtenir un ID
                $this->entityManager->persist($progression);
                try {
                    $this->entityManager->flush();
                    error_log("Progression persistée avec ID: " . $progression->getId());
                } catch (\Exception $e) {
                    error_log("Erreur lors de la persistance de la progression: " . $e->getMessage());
                    error_log("Trace: " . $e->getTraceAsString());

                    // Essayer de récupérer une progression existante
                    try {
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT p.id FROM progression p
                                WHERE p.cours_id = :coursId
                                AND p.apprenant_id = :apprenantId
                                LIMIT 1';

                        $stmt = $connection->prepare($sql);
                        $resultSet = $stmt->executeQuery([
                            'coursId' => $cours->getId(),
                            'apprenantId' => $apprenant->getId()
                        ]);

                        $result = $resultSet->fetchAssociative();

                        if ($result && isset($result['id'])) {
                            $progressionId = $result['id'];
                            $progression = $this->progressionRepository->find($progressionId);
                            error_log("Progression existante récupérée avec ID: " . $progressionId);
                        }
                    } catch (\Exception $e2) {
                        error_log("Erreur lors de la récupération de progression: " . $e2->getMessage());
                    }
                }
            }

            // Vérifier que la progression a un ID valide
            if (!$progression || !$progression->getId()) {
                error_log("Erreur: Progression invalide ou sans ID");
                return $this->json([
                    'error' => 'Invalid progression',
                    'message' => 'Impossible de générer un certificat sans progression valide.'
                ], 500);
            }

            // Récupérer la progression fraîchement depuis la base de données pour éviter les problèmes d'état
            try {
                $refreshedProgression = $this->progressionRepository->find($progression->getId());
                if (!$refreshedProgression) {
                    error_log("Erreur: Impossible de récupérer la progression avec l'ID " . $progression->getId());

                    // Essayer de récupérer la progression directement depuis la base de données
                    try {
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT * FROM progression WHERE id = :id';
                        $stmt = $connection->prepare($sql);
                        $resultSet = $stmt->executeQuery(['id' => $progression->getId()]);
                        $progressionData = $resultSet->fetchAssociative();

                        if ($progressionData) {
                            error_log("Progression trouvée dans la base de données mais pas via le repository");
                            // Créer une nouvelle progression avec les mêmes données
                            $refreshedProgression = new Progression();
                            $refreshedProgression->setCours($cours);
                            $refreshedProgression->setApprenant($apprenant);
                            $refreshedProgression->setEvaluation($progression->getEvaluation());
                            $refreshedProgression->setTableEvaluations($progression->getTableEvaluations());

                            $this->entityManager->persist($refreshedProgression);
                            $this->entityManager->flush();
                            error_log("Nouvelle progression créée avec ID: " . $refreshedProgression->getId());
                        } else {
                            error_log("Progression introuvable dans la base de données");
                            return $this->json([
                                'error' => 'Progression not found',
                                'message' => 'Impossible de récupérer la progression nécessaire pour le certificat.'
                            ], 500);
                        }
                    } catch (\Exception $e2) {
                        error_log("Erreur lors de la récupération directe de la progression: " . $e2->getMessage());
                        return $this->json([
                            'error' => 'Progression not found',
                            'message' => 'Impossible de récupérer la progression nécessaire pour le certificat.'
                        ], 500);
                    }
                }

                // Associer la progression au certificat
                try {
                    // Vérifier que la progression a bien un ID et est correctement persistée
                    if (!$refreshedProgression->getId()) {
                        error_log("ERREUR CRITIQUE: La progression n'a toujours pas d'ID après refresh");

                        // Forcer une nouvelle persistance
                        $this->entityManager->persist($refreshedProgression);
                        $this->entityManager->flush();

                        if (!$refreshedProgression->getId()) {
                            throw new \Exception("Impossible de persister la progression");
                        }
                    }

                    // Vérifier que la progression a bien un cours et un apprenant associés
                    if (!$refreshedProgression->getCours() || !$refreshedProgression->getApprenant()) {
                        error_log("ERREUR: La progression n'a pas de cours ou d'apprenant associé");

                        // Réassocier le cours et l'apprenant
                        if (!$refreshedProgression->getCours()) {
                            $refreshedProgression->setCours($cours);
                            error_log("Cours réassocié à la progression");
                        }

                        if (!$refreshedProgression->getApprenant()) {
                            $refreshedProgression->setApprenant($apprenant);
                            error_log("Apprenant réassocié à la progression");
                        }

                        // Persister les changements
                        $this->entityManager->flush();
                    }

                    // Associer la progression au certificat en désactivant temporairement la vérification bidirectionnelle
                    $certificat->setProgression($refreshedProgression);
                    error_log("Progression associée au certificat, ID: " . $refreshedProgression->getId());
                } catch (\Exception $e) {
                    error_log("Erreur lors de l'association de la progression au certificat: " . $e->getMessage());
                    error_log("Trace: " . $e->getTraceAsString());

                    // Essayer une approche alternative
                    try {
                        error_log("Tentative d'approche alternative pour associer la progression");

                        // Utiliser la réflexion pour définir directement la propriété progression
                        $reflectionClass = new \ReflectionClass(Certificat::class);
                        $property = $reflectionClass->getProperty('progression');
                        $property->setAccessible(true);
                        $property->setValue($certificat, $refreshedProgression);

                        error_log("Progression associée au certificat via réflexion");
                    } catch (\Exception $e2) {
                        error_log("Erreur lors de l'approche alternative: " . $e2->getMessage());
                        error_log("Trace: " . $e2->getTraceAsString());

                        // Dernière tentative: créer un nouveau certificat sans progression
                        error_log("Tentative de création d'un certificat sans progression");

                        // On continue sans progression, on essaiera de l'associer après la persistance
                    }
                }
            } catch (\Exception $e) {
                error_log("Erreur lors de la récupération de la progression: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());

                return $this->json([
                    'error' => 'Association error',
                    'message' => 'Erreur lors de l\'association de la progression au certificat.'
                ], 500);
            }

            // Vérifier l'état du certificat avant la persistance
            error_log('État du certificat avant persistance:');
            error_log('- ID: ' . ($certificat->getId() ?: 'non défini'));
            error_log('- Date obtention: ' . ($certificat->getDateObtention() ? $certificat->getDateObtention()->format('Y-m-d') : 'non définie'));
            error_log('- Apprenant ID: ' . ($certificat->getApprenant() ? $certificat->getApprenant()->getId() : 'non défini'));
            error_log('- Progression ID: ' . ($certificat->getProgression() ? $certificat->getProgression()->getId() : 'non défini'));
            error_log('- Contenu: ' . ($certificat->getContenu() ? 'défini' : 'non défini'));

            // Persister le certificat
            try {
                $this->entityManager->persist($certificat);
                error_log('Certificat persisté avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance du certificat: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());

                return $this->json([
                    'error' => 'Persistence error',
                    'message' => 'Erreur lors de la persistance du certificat.'
                ], 500);
            }

            // Créer une notification
            $notification = new Notification();
            $notification->setDescription("Félicitations ! Vous avez obtenu un certificat pour le cours " . $cours->getTitre());
            $notification->setCertificat($certificat);
            $notification->setUser($apprenant->getUtilisateur());

            $this->entityManager->persist($notification);

            // Log avant flush
            error_log('Avant flush - Certificat ID: ' . ($certificat->getId() ?: 'non défini') . ', Contenu: ' . ($certificat->getContenu() ?: 'non défini'));

            // Flush avec gestion d'erreur
            try {
                // Vérifier l'état de l'EntityManager avant le flush
                if (!$this->entityManager->isOpen()) {
                    error_log('ERREUR: EntityManager fermé, tentative de réouverture');
                    // Récupérer un nouvel EntityManager
                    $this->entityManager = $this->entityManager->create(
                        $this->entityManager->getConnection(),
                        $this->entityManager->getConfiguration()
                    );

                    // Repersister les entités
                    $this->entityManager->persist($certificat);
                    $this->entityManager->persist($notification);
                }

                // Essayer de flusher avec un try/catch spécifique pour chaque entité
                try {
                    $this->entityManager->flush($certificat);
                    error_log('Flush du certificat réussi, ID: ' . $certificat->getId());
                } catch (\Exception $e1) {
                    error_log('Erreur lors du flush du certificat: ' . $e1->getMessage());
                    // Continuer pour essayer de flusher la notification
                }

                try {
                    $this->entityManager->flush($notification);
                    error_log('Flush de la notification réussi');
                } catch (\Exception $e1) {
                    error_log('Erreur lors du flush de la notification: ' . $e1->getMessage());
                    // Continuer car l'erreur sur la notification n'est pas critique
                }

                // Vérifier si le certificat a bien été persisté
                if (!$certificat->getId()) {
                    throw new \Exception("Le certificat n'a pas été correctement persisté");
                }

                error_log('Flush global réussi');
            } catch (\Exception $e) {
                error_log('Erreur lors du flush: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());

                // Essayer de récupérer plus d'informations sur l'erreur
                if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                    error_log('Violation de contrainte d\'unicité détectée');

                    // Vérifier si un certificat existe déjà
                    try {
                        $connection = $this->entityManager->getConnection();
                        $sql = 'SELECT c.id FROM certificat c
                               JOIN progression p ON c.progression_id = p.id
                               WHERE p.apprenant_id = :apprenantId AND p.cours_id = :coursId
                               LIMIT 1';
                        $stmt = $connection->prepare($sql);
                        $resultSet = $stmt->executeQuery([
                            'apprenantId' => $apprenant->getId(),
                            'coursId' => $cours->getId()
                        ]);
                        $existingCertificatData = $resultSet->fetchAssociative();

                        if ($existingCertificatData) {
                            error_log('Certificat existant trouvé avec ID: ' . $existingCertificatData['id']);
                            $existingCertificat = $this->certificatRepository->find($existingCertificatData['id']);
                            if ($existingCertificat) {
                                return $this->json([
                                    'message' => 'Certificate already exists',
                                    'certificat' => [
                                        'id' => $existingCertificat->getId(),
                                        'date_obtention' => $existingCertificat->getDateObtention()->format('Y-m-d'),
                                        'apprenant' => [
                                            'id' => $apprenant->getId(),
                                            'name' => $apprenant->getName(),
                                            'email' => $apprenant->getEmail()
                                        ],
                                        'cours' => [
                                            'id' => $cours->getId(),
                                            'titre' => $cours->getTitre(),
                                            'description' => $cours->getDescription()
                                        ]
                                    ]
                                ], 200);
                            }
                        }
                    } catch (\Exception $e2) {
                        error_log('Erreur lors de la recherche de certificat existant: ' . $e2->getMessage());
                    }
                }

                // Essayer une dernière approche: insertion directe en SQL
                try {
                    error_log('Tentative d\'insertion directe en SQL');
                    $connection = $this->entityManager->getConnection();

                    // Vérifier si la progression existe
                    $sql = 'SELECT id FROM progression WHERE id = :id';
                    $stmt = $connection->prepare($sql);
                    $resultSet = $stmt->executeQuery(['id' => $refreshedProgression->getId()]);
                    $progressionExists = $resultSet->fetchAssociative();

                    if ($progressionExists) {
                        // Insérer le certificat directement sans utiliser apprenant_id
                        $sql = 'INSERT INTO certificat (date_obtention, contenu, progression_id)
                                VALUES (:date, :contenu, :progression_id)';
                        $stmt = $connection->prepare($sql);
                        $stmt->executeStatement([
                            'date' => (new \DateTime())->format('Y-m-d'),
                            'contenu' => $contenuCertificat,
                            'progression_id' => $refreshedProgression->getId()
                        ]);

                        // Récupérer l'ID du certificat inséré
                        $certificatId = $connection->lastInsertId();
                        error_log('Certificat inséré directement en SQL avec ID: ' . $certificatId);

                        // Retourner les données du certificat
                        return $this->json([
                            'message' => 'Certificate generated successfully (SQL)',
                            'certificat' => [
                                'id' => (int)$certificatId,
                                'date_obtention' => (new \DateTime())->format('Y-m-d'),
                                'apprenant' => [
                                    'id' => $apprenant->getId(),
                                    'name' => $apprenant->getName(),
                                    'email' => $apprenant->getEmail()
                                ],
                                'cours' => [
                                    'id' => $cours->getId(),
                                    'titre' => $cours->getTitre(),
                                    'description' => $cours->getDescription()
                                ],
                                'competences' => $competences
                            ]
                        ], 201);
                    } else {
                        error_log('La progression n\'existe pas dans la base de données');
                    }
                } catch (\Exception $e3) {
                    error_log('Erreur lors de l\'insertion directe en SQL: ' . $e3->getMessage());
                }

                return $this->json([
                    'error' => 'Database error',
                    'message' => 'Une erreur est survenue lors de l\'enregistrement du certificat. Veuillez réessayer.',
                    'details' => $e->getMessage()
                ], 500);
            }

            // Log après flush
            error_log('Après flush - Certificat ID: ' . $certificat->getId() . ', Contenu: ' . ($certificat->getContenu() ?: 'non défini'));

            // Envoyer un email de notification
            try {
                $this->emailService->sendCertificateNotificationEmail(
                    $apprenant->getEmail(),
                    $apprenant->getName(),
                    $cours->getTitre(),
                    $certificat->getId()
                );
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas bloquer le processus
                error_log('Erreur lors de l\'envoi de l\'email de notification de certificat: ' . $e->getMessage());
            }

            // Récupérer les compétences acquises
            $competences = [];

            foreach ($quizzes as $quiz) {
                $evaluation = $this->evaluationRepository->findOneBy(['quiz' => $quiz]);
                if ($evaluation && $evaluation->getStatutEvaluation() === 'Satisfaisant') {
                    // Ajouter les compétences du quiz
                    $competences[] = $quiz->getNomFR() ?? 'Compétence ' . $quiz->getIDModule();
                }
            }

            // Récupérer le contenu du certificat
            $contenuCertificat = $certificat->getContenu() ? json_decode($certificat->getContenu(), true) : null;

            // Log pour le débogage
            error_log('Contenu du certificat récupéré pour la réponse: ' . ($certificat->getContenu() ?: 'non défini'));

            // Vérifier que le certificat a bien un ID
            if (!$certificat->getId()) {
                error_log("ERREUR CRITIQUE: Le certificat n'a pas d'ID après le flush");

                // Essayer de récupérer le certificat par d'autres moyens
                try {
                    $connection = $this->entityManager->getConnection();
                    $sql = 'SELECT c.id FROM certificat c
                           JOIN progression p ON c.progression_id = p.id
                           WHERE p.apprenant_id = :apprenantId AND p.cours_id = :coursId
                           ORDER BY c.id DESC
                           LIMIT 1';
                    $stmt = $connection->prepare($sql);
                    $resultSet = $stmt->executeQuery([
                        'apprenantId' => $apprenant->getId(),
                        'coursId' => $cours->getId()
                    ]);
                    $certificatData = $resultSet->fetchAssociative();

                    if ($certificatData) {
                        $certificatId = $certificatData['id'];
                        error_log("Certificat trouvé en base avec ID: " . $certificatId);

                        // Récupérer le certificat complet
                        $certificat = $this->certificatRepository->find($certificatId);
                    } else {
                        error_log("Aucun certificat trouvé en base pour cet apprenant et ce cours");
                    }
                } catch (\Exception $e) {
                    error_log("Erreur lors de la recherche du certificat: " . $e->getMessage());
                }
            }

            // Préparer la réponse
            $responseData = [
                'message' => 'Certificate generated successfully',
                'certificat' => [
                    'id' => $certificat->getId(),
                    'date_obtention' => $certificat->getDateObtention()->format('Y-m-d'),
                    'apprenant' => [
                        'id' => $apprenant->getId(),
                        'name' => $apprenant->getName(),
                        'email' => $apprenant->getEmail()
                    ],
                    'cours' => [
                        'id' => $cours->getId(),
                        'titre' => $cours->getTitre(),
                        'description' => $cours->getDescription()
                    ],
                    'competences' => $competences
                ]
            ];

            // Ajouter le numéro de certificat si l'ID est disponible
            if ($certificat->getId()) {
                $responseData['certificat']['numero_certificat'] = sprintf('CERT-%06d', $certificat->getId());
            }

            // Ajouter le contenu si disponible
            if ($certificat->getContenu()) {
                $responseData['certificat']['contenu'] = $certificat->getContenu();
            } else if ($contenuCertificat) {
                $responseData['certificat']['contenu'] = $contenuCertificat;
            }

            return $this->json($responseData, 201);
        } catch (\Doctrine\ORM\Exception\ORMException $e) {
            // Log l'erreur pour le débogage avec plus de détails
            error_log('Erreur ORM lors de la génération du certificat: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            // Vérifier si l'erreur est liée à une contrainte d'intégrité
            if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                error_log('Erreur de contrainte d\'intégrité détectée');
                return $this->json([
                    'error' => 'Database error',
                    'message' => 'Une erreur de contrainte d\'intégrité est survenue. Un certificat existe peut-être déjà pour ce cours.'
                ], 500);
            }

            return $this->json([
                'error' => 'Database error',
                'message' => 'Une erreur est survenue lors de l\'enregistrement du certificat. Veuillez réessayer.'
            ], 500);
        } catch (\Exception $e) {
            // Log l'erreur pour le débogage avec plus de détails
            error_log('Erreur lors de la génération du certificat: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());
            error_log('Type d\'exception: ' . get_class($e));

            return $this->json([
                'error' => 'Server error',
                'message' => 'Une erreur est survenue lors de la génération du certificat. Veuillez réessayer.'
            ], 500);
        }
    }

    #[Route('/apprenant/{apprenantId}', name: 'api_certificat_by_apprenant', methods: ['GET'])]
    public function getCertificatsByApprenant(int $apprenantId): JsonResponse
    {
        try {
            // Log pour le débogage
            error_log("Recherche des certificats pour l'apprenant ID: {$apprenantId}");

            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                error_log("Apprenant non trouvé avec ID: {$apprenantId}");
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            // Récupérer tous les certificats de l'apprenant
            try {
                // Now we can directly query by apprenant since Certificat is the owning side
                $certificats = $this->certificatRepository->findBy([
                    'apprenant' => $apprenant
                ]);
                error_log("Nombre de certificats trouvés: " . count($certificats));
            } catch (\Exception $e) {
                error_log("Erreur lors de la recherche des certificats: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());

                // Essayer une approche alternative avec DQL
                try {
                    $em = $this->entityManager;
                    $query = $em->createQuery(
                        'SELECT c FROM App\Entity\Certificat c WHERE c.apprenant = :apprenant'
                    )->setParameter('apprenant', $apprenant);

                    $certificats = $query->getResult();
                    error_log("Nombre de certificats trouvés avec DQL: " . count($certificats));
                } catch (\Exception $e2) {
                    error_log("Erreur lors de la recherche DQL: " . $e2->getMessage());
                    $certificats = [];
                }
            }

            if (empty($certificats)) {
                error_log("Aucun certificat trouvé pour l'apprenant {$apprenantId}");
                return $this->json([
                    'message' => 'No certificates found for this apprenant',
                    'certificats' => []
                ], 200);
            }

            $certificatsData = [];

            foreach ($certificats as $certificat) {
                try {
                    // Récupérer le cours associé à la progression du certificat
                    $progression = $certificat->getProgression();
                    if (!$progression) {
                        error_log("Progression non trouvée pour le certificat ID: " . $certificat->getId());
                        continue;
                    }

                    $cours = $progression->getCours();
                    if (!$cours) {
                        error_log("Cours non trouvé pour la progression ID: " . $progression->getId());
                        continue;
                    }

                    $certificatsData[] = [
                        'id' => $certificat->getId(),
                        'date_obtention' => $certificat->getDateObtention()->format('Y-m-d'),
                        'cours' => [
                            'id' => $cours->getId(),
                            'titre' => $cours->getTitre()
                        ],
                        'download_link' => '/api/certificat/' . $certificat->getId() . '/download'
                    ];
                } catch (\Exception $e) {
                    error_log("Erreur lors du traitement du certificat ID " . $certificat->getId() . ": " . $e->getMessage());
                    continue;
                }
            }

            if (empty($certificatsData)) {
                error_log("Aucun certificat valide trouvé pour l'apprenant {$apprenantId}");
                return $this->json([
                    'message' => 'No valid certificates found for this apprenant',
                    'certificats' => []
                ], 200);
            }

            return $this->json([
                'certificats' => $certificatsData
            ], 200);
        } catch (\Exception $e) {
            error_log("Erreur générale dans getCertificatsByApprenant: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/apprenant/{apprenantId}/cours/{coursId}', name: 'api_certificat_by_apprenant_and_cours', methods: ['GET'])]
    public function getCertificatByApprenantAndCours(int $apprenantId, int $coursId): JsonResponse
    {
        try {
            // Log pour le débogage
            error_log("Recherche de certificat pour apprenant ID: {$apprenantId} et cours ID: {$coursId}");

            $apprenant = $this->apprenantRepository->find($apprenantId);
            if (!$apprenant) {
                error_log("Apprenant non trouvé avec ID: {$apprenantId}");
                return $this->json(['error' => 'Apprenant not found'], 404);
            }

            $cours = $this->coursRepository->find($coursId);
            if (!$cours) {
                error_log("Cours non trouvé avec ID: {$coursId}");
                return $this->json(['error' => 'Cours not found'], 404);
            }

            // Récupérer tous les certificats de l'apprenant
            // Now we can directly query by apprenant since Certificat is the owning side
            try {
                $certificats = $this->certificatRepository->findBy([
                    'apprenant' => $apprenant
                ]);
                error_log("Nombre de certificats trouvés pour l'apprenant: " . count($certificats));
            } catch (\Exception $e) {
                error_log("Erreur lors de la recherche des certificats: " . $e->getMessage());
                error_log("Trace: " . $e->getTraceAsString());

                // Essayer une approche alternative avec DQL
                try {
                    $em = $this->entityManager;
                    $query = $em->createQuery(
                        'SELECT c FROM App\Entity\Certificat c WHERE c.apprenant = :apprenant'
                    )->setParameter('apprenant', $apprenant);

                    $certificats = $query->getResult();
                    error_log("Nombre de certificats trouvés avec DQL: " . count($certificats));
                } catch (\Exception $e2) {
                    error_log("Erreur lors de la recherche DQL: " . $e2->getMessage());
                    $certificats = [];
                }
            }

            $certificat = null;

            // Vérifier si l'un des certificats correspond au cours actuel
            foreach ($certificats as $cert) {
                try {
                    $progression = $cert->getProgression();
                    if ($progression && $progression->getCours() && $progression->getCours()->getId() === $cours->getId()) {
                        $certificat = $cert;
                        error_log("Certificat trouvé pour le cours: " . $cours->getId());
                        break;
                    }
                } catch (\Exception $e) {
                    error_log("Erreur lors de la vérification du certificat: " . $e->getMessage());
                    continue;
                }
            }

            if (!$certificat) {
                error_log("Aucun certificat trouvé pour l'apprenant {$apprenantId} et le cours {$coursId}");
                return $this->json([
                    'message' => 'No certificate found for this apprenant and course',
                    'certificat' => null
                ], 200);
            }

            $result = [
                'certificat' => [
                    'id' => $certificat->getId(),
                    'date_obtention' => $certificat->getDateObtention()->format('Y-m-d'),
                    'cours' => [
                        'id' => $cours->getId(),
                        'titre' => $cours->getTitre()
                    ],
                    'download_link' => '/api/certificat/' . $certificat->getId() . '/download'
                ]
            ];

            error_log("Réponse préparée: " . json_encode($result));
            return $this->json($result, 200);
        } catch (\Exception $e) {
            error_log("Erreur générale dans getCertificatByApprenantAndCours: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());

            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/download', name: 'api_certificat_download', methods: ['GET'])]
    public function download(int $id): \Symfony\Component\HttpFoundation\Response
    {
        try {
            error_log("Début de la méthode download pour le certificat ID: $id");

            // Récupérer le certificat directement via le repository pour plus de fiabilité
            $certificat = $this->certificatRepository->find($id);
            if (!$certificat) {
                error_log("Certificat non trouvé avec ID: $id");
                return $this->json(['error' => 'Certificat not found'], 404);
            }

            error_log("Certificat trouvé avec ID: " . $certificat->getId());

            // Récupérer l'apprenant
            $apprenant = $certificat->getApprenant();
            if (!$apprenant) {
                // Essayer de récupérer l'apprenant via la progression
                if ($certificat->getProgression() && $certificat->getProgression()->getApprenant()) {
                    $apprenant = $certificat->getProgression()->getApprenant();
                    error_log("Apprenant récupéré via progression: " . $apprenant->getId());
                } else {
                    error_log("Apprenant non trouvé pour le certificat ID: $id");
                    return $this->json([
                        'error' => 'Apprenant not found',
                        'message' => 'Impossible de trouver l\'apprenant associé à ce certificat'
                    ], 500);
                }
            }

            // Récupérer le cours via la progression
            $cours = null;
            if ($certificat->getProgression() && $certificat->getProgression()->getCours()) {
                $cours = $certificat->getProgression()->getCours();
                error_log("Cours récupéré via progression: " . $cours->getId());
            } else {
                error_log("Cours non trouvé pour le certificat ID: $id");
                return $this->json([
                    'error' => 'Cours not found',
                    'message' => 'Impossible de trouver le cours associé à ce certificat'
                ], 500);
            }

            // Vérifier si le certificat a déjà un contenu JSON
            $contenuExistant = $certificat->getContenu();
            $contenuCertificat = null;

            if ($contenuExistant) {
                try {
                    $contenuCertificat = json_decode($contenuExistant, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Erreur de décodage JSON du contenu existant: " . json_last_error_msg());
                        $contenuCertificat = null;
                    } else {
                        error_log("Contenu JSON existant récupéré avec succès");
                    }
                } catch (\Exception $e) {
                    error_log("Exception lors du décodage JSON: " . $e->getMessage());
                    $contenuCertificat = null;
                }
            }

            // Si pas de contenu valide, créer un nouveau contenu
            if (!$contenuCertificat) {
                error_log("Création d'un nouveau contenu pour le certificat");

                // Récupérer les compétences (quizzes) du cours
                $quizzes = $this->quizRepository->findBy(['cours' => $cours]);
                $competences = [];

                foreach ($quizzes as $quiz) {
                    $competences[] = $quiz->getNomFR() ?? 'Compétence ' . $quiz->getIDModule();
                }

                // Créer le contenu du certificat
                $contenuCertificat = [
                    'id' => $certificat->getId(),
                    'date_obtention' => $certificat->getDateObtention()->format('Y-m-d'),
                    'apprenant' => [
                        'id' => $apprenant->getId(),
                        'name' => $apprenant->getName(),
                        'email' => $apprenant->getEmail()
                    ],
                    'cours' => [
                        'id' => $cours->getId(),
                        'titre' => $cours->getTitre(),
                        'description' => $cours->getDescription()
                    ],
                    'competences' => $competences,
                    'numero_certificat' => sprintf('CERT-%06d', $certificat->getId()),
                    'isAutoGenerated' => $certificat->isIsAutoGenerated()
                ];

                // Sauvegarder le contenu pour les futures requêtes
                try {
                    $certificat->setContenu(json_encode($contenuCertificat));
                    $this->entityManager->flush();
                    error_log("Nouveau contenu du certificat enregistré avec succès");
                } catch (\Exception $e) {
                    error_log("Erreur lors de l'enregistrement du contenu du certificat: " . $e->getMessage());
                    // Continuer quand même avec le contenu généré
                }
            }

            // Renvoyer les données du certificat au format JSON
            return $this->json([
                'certificat' => $contenuCertificat
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur lors de la récupération du certificat: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            return $this->json([
                'error' => 'Server error',
                'message' => 'Une erreur est survenue lors de la récupération du certificat: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/data', name: 'api_certificat_data', methods: ['GET'])]
    public function getCertificatData(int $id): JsonResponse
    {
        try {
            error_log("Début de la méthode getCertificatData pour le certificat ID: $id");

            // Récupérer le certificat directement via le repository pour plus de fiabilité
            $certificat = $this->certificatRepository->find($id);
            if (!$certificat) {
                error_log("Certificat non trouvé avec ID: $id");
                return $this->json(['error' => 'Certificat not found'], 404);
            }

            error_log("Certificat trouvé avec ID: " . $certificat->getId());

            // Récupérer l'apprenant
            $apprenant = $certificat->getApprenant();
            if (!$apprenant) {
                // Essayer de récupérer l'apprenant via la progression
                if ($certificat->getProgression() && $certificat->getProgression()->getApprenant()) {
                    $apprenant = $certificat->getProgression()->getApprenant();
                    error_log("Apprenant récupéré via progression: " . $apprenant->getId());
                } else {
                    error_log("Apprenant non trouvé pour le certificat ID: $id");
                    return $this->json([
                        'error' => 'Apprenant not found',
                        'message' => 'Impossible de trouver l\'apprenant associé à ce certificat'
                    ], 500);
                }
            }

            // Récupérer le cours via la progression
            $cours = null;
            if ($certificat->getProgression() && $certificat->getProgression()->getCours()) {
                $cours = $certificat->getProgression()->getCours();
                error_log("Cours récupéré via progression: " . $cours->getId());
            } else {
                error_log("Cours non trouvé pour le certificat ID: $id");
                return $this->json([
                    'error' => 'Cours not found',
                    'message' => 'Impossible de trouver le cours associé à ce certificat'
                ], 500);
            }

            // Vérifier si le certificat a déjà un contenu JSON
            $contenuExistant = $certificat->getContenu();
            $contenuCertificat = null;

            if ($contenuExistant) {
                try {
                    $contenuCertificat = json_decode($contenuExistant, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        error_log("Erreur de décodage JSON du contenu existant: " . json_last_error_msg());
                        $contenuCertificat = null;
                    } else {
                        error_log("Contenu JSON existant récupéré avec succès");
                    }
                } catch (\Exception $e) {
                    error_log("Exception lors du décodage JSON: " . $e->getMessage());
                    $contenuCertificat = null;
                }
            }

            // Si pas de contenu valide, créer un nouveau contenu
            if (!$contenuCertificat) {
                error_log("Création d'un nouveau contenu pour le certificat");

                // Récupérer les compétences (quizzes) du cours
                $quizzes = $this->quizRepository->findBy(['cours' => $cours]);
                $competences = [];

                foreach ($quizzes as $quiz) {
                    $competences[] = $quiz->getNomFR() ?? 'Compétence ' . $quiz->getIDModule();
                }

                // Créer le contenu du certificat
                $contenuCertificat = [
                    'id' => $certificat->getId(),
                    'date_obtention' => $certificat->getDateObtention()->format('Y-m-d'),
                    'apprenant' => [
                        'id' => $apprenant->getId(),
                        'name' => $apprenant->getName(),
                        'email' => $apprenant->getEmail()
                    ],
                    'cours' => [
                        'id' => $cours->getId(),
                        'titre' => $cours->getTitre(),
                        'description' => $cours->getDescription()
                    ],
                    'competences' => $competences,
                    'numero_certificat' => sprintf('CERT-%06d', $certificat->getId()),
                    'isAutoGenerated' => $certificat->isIsAutoGenerated()
                ];

                // Sauvegarder le contenu pour les futures requêtes
                try {
                    $certificat->setContenu(json_encode($contenuCertificat));
                    $this->entityManager->flush();
                    error_log("Nouveau contenu du certificat enregistré avec succès");
                } catch (\Exception $e) {
                    error_log("Erreur lors de l'enregistrement du contenu du certificat: " . $e->getMessage());
                    // Continuer quand même avec le contenu généré
                }
            }

            // Renvoyer les données du certificat au format JSON
            return $this->json([
                'certificat' => $contenuCertificat
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur lors de la récupération des données du certificat: ' . $e->getMessage());
            error_log('Trace: ' . $e->getTraceAsString());

            return $this->json([
                'error' => 'Server error',
                'message' => 'Une erreur est survenue lors de la récupération des données du certificat: ' . $e->getMessage()
            ], 500);
        }
    }
}
