<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Repository\CoursRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/quiz')]
class QuizController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CoursRepository $coursRepository,
        private ValidatorInterface $validator
    ) {}

    #[Route('/batch', name: 'api_quiz_create_batch', methods: ['POST'])]
    public function createBatch(Request $request): JsonResponse
    {
        $connection = $this->em->getConnection();
        $connection->beginTransaction();

        try {
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid JSON data: ' . json_last_error_msg(),
                ], 400);
            }

            if (!is_array($data)) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid data format: expected JSON array',
                ], 400);
            }

            $quizRepository = $this->em->getRepository(Quiz::class);
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($data as $index => $item) {
                $result = ['index' => $index];
                try {
                    // Validation des champs requis
                    $requiredFields = ['cours', 'IDModule', 'Nom_FR', 'Nom_EN'];
                    $missingFields = [];
                    foreach ($requiredFields as $field) {
                        if (!isset($item[$field]) || empty($item[$field])) {
                            $missingFields[] = $field;
                        }
                    }

                    if (!empty($missingFields)) {
                        throw new \InvalidArgumentException("Champs requis manquants: " . implode(', ', $missingFields));
                    }

                    // Vérification de l'existence du cours
                    $cours = $this->coursRepository->find($item['cours']);
                    if (!$cours) {
                        throw new \InvalidArgumentException("Cours avec l'ID {$item['cours']} non trouvé");
                    }

                    // Vérification des doublons
                    $existingQuiz = $quizRepository->findOneBy([
                        'IDModule' => $item['IDModule'],
                        'cours' => $cours
                    ]);

                    if ($existingQuiz) {
                        throw new \InvalidArgumentException("Un quiz avec cet IDModule existe déjà pour ce cours");
                    }

                    // Création du nouveau quiz avec valeurs par défaut
                    $quiz = new Quiz();
                    $quiz->setCours($cours);
                    $quiz->setIDModule($item['IDModule']);
                    $quiz->setType($item['Type'] ?? 'Training');
                    $quiz->setCategory($item['Category'] ?? 'Sterile');
                    $quiz->setMainSurface($item['MainSurface'] ?? false);
                    $quiz->setSurface($item['Surface'] ?? 0); // Valeur par défaut
                    $quiz->setMain($item['Main'] ?? 0);
                    $quiz->setNomFR($item['Nom_FR']);
                    $quiz->setNomEN($item['Nom_EN']);
                    $quiz->setCompetenceID($item['Competence_ID'] ?? 0);

                    // Champs nullable
                    $nullableFields = [
                        'PointFort_FR', 'PointFort_EN', 'Comp_Categorie_FR', 'Comp_Categorie_EN',
                        'Competence_Nom_FR', 'Competence_Nom_EN', 'SousCompetence_Nom_FR',
                        'SousCompetence_Nom_EN', 'Action_Nom_FR', 'Action_Nom_EN',
                        'Action_Categorie_FR', 'Action_Categorie_EN'
                    ];

                    foreach ($nullableFields as $field) {
                        if (isset($item[$field])) {
                            $setter = 'set' . str_replace('_', '', $field);
                            if (method_exists($quiz, $setter)) {
                                $quiz->$setter($item[$field]);
                            }
                        }
                    }

                    // Validation de l'entité
                    $errors = $this->validator->validate($quiz);
                    if (count($errors) > 0) {
                        $errorMessages = [];
                        foreach ($errors as $error) {
                            $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                        }
                        throw new \InvalidArgumentException(implode(', ', $errorMessages));
                    }

                    $this->em->persist($quiz);
                    $result['success'] = true;
                    $result['quiz_id'] = $quiz->getId();
                    $successCount++;
                } catch (\Exception $e) {
                    $result['success'] = false;
                    $result['error'] = $e->getMessage();
                    $errorCount++;

                    if (!$this->em->isOpen()) {
                        $this->em = $this->em->create(
                            $this->em->getConnection(),
                            $this->em->getConfiguration()
                        );
                    }
                }
                $results[] = $result;
            }

            if ($successCount > 0) {
                $this->em->flush();
                $connection->commit();

                return $this->json([
                    'status' => $errorCount > 0 ? 'partial' : 'complete',
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'results' => $results,
                ]);
            } else {
                $connection->rollBack();
                return $this->json([
                    'status' => 'failed',
                    'error_count' => $errorCount,
                    'results' => $results,
                ], 400);
            }
        } catch (\Exception $e) {
            $connection->rollBack();
            return $this->json([
                'status' => 'error',
                'message' => 'Traitement par lot échoué',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('', name: 'api_quiz_list', methods: ['GET'])]
    public function list(Request $request, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $courseId = $request->query->get('cours');
            $IDModule = $request->query->get('IDModule');

            if ($courseId) {
                $cours = $this->coursRepository->find($courseId);
                if (!$cours) {
                    return $this->json(['error' => 'Course not found'], 404);
                }
                $quizzes = $quizRepository->findBy(['cours' => $cours]);
            } elseif ($IDModule) {
                $quizzes = $quizRepository->findBy(['IDModule' => $IDModule]);
            } else {
                $quizzes = $quizRepository->findAll();
            }

            $data = array_map(function($quiz) {
                return [
                    'id' => $quiz->getId(),
                    'IDModule' => $quiz->getIDModule(),
                    'Nom_FR' => $quiz->getNomFR(),
                    'Nom_EN' => $quiz->getNomEN(),
                    'cours_id' => $quiz->getCours()?->getId(),
                    'Type' => $quiz->getType(),
                    'Category' => $quiz->getCategory(),
                    'MainSurface' => $quiz->isMainSurface(),
                    'Main' => $quiz->getMain(),
                    'Surface' => $quiz->getSurface(),
                    'Competence_ID' => $quiz->getCompetenceID(),
                    'Comp_Categorie_FR' => $quiz->getCompCategorieFR(),
                    'Comp_Categorie_EN' => $quiz->getCompCategorieEN(),
                    'Competence_Nom_FR' => $quiz->getCompetenceNomFR(),
                    'Competence_Nom_EN' => $quiz->getCompetenceNomEN(),
                    'SousCompetence_Nom_FR' => $quiz->getSousCompetenceNomFR(),
                    'SousCompetence_Nom_EN' => $quiz->getSousCompetenceNomEN(),
                    'Action_Nom_FR' => $quiz->getActionNomFR(),
                    'Action_Nom_EN' => $quiz->getActionNomEN(),
                    'Action_Categorie_FR' => $quiz->getActionCategorieFR(),
                    'Action_Categorie_EN' => $quiz->getActionCategorieEN()
                ];
            }, $quizzes);

            return $this->json($data);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{IDModule}', name: 'api_quiz_show', methods: ['GET'])]
    public function show(string $IDModule, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $quiz = $quizRepository->findOneBy(['IDModule' => $IDModule]);
            if (!$quiz) {
                return $this->json(['message' => 'Quiz not found'], 404);
            }

            return $this->json([
                'id' => $quiz->getId(),
                'IDModule' => $quiz->getIDModule(),
                'Nom_FR' => $quiz->getNomFR(),
                'Nom_EN' => $quiz->getNomEN(),
                'Type' => $quiz->getType(),
                'Category' => $quiz->getCategory(),
                'MainSurface' => $quiz->isMainSurface(),
                'cours_id' => $quiz->getCours()?->getId(),
                'Competence_ID' => $quiz->getCompetenceID(),
                'Comp_Categorie_FR' => $quiz->getCompCategorieFR(),
                'Comp_Categorie_EN' => $quiz->getCompCategorieEN(),
                'Competence_Nom_FR' => $quiz->getCompetenceNomFR(),
                'Competence_Nom_EN' => $quiz->getCompetenceNomEN(),
                'SousCompetence_Nom_FR' => $quiz->getSousCompetenceNomFR(),
                'SousCompetence_Nom_EN' => $quiz->getSousCompetenceNomEN(),
                'Action_Nom_FR' => $quiz->getActionNomFR(),
                'Action_Nom_EN' => $quiz->getActionNomEN(),
                'Action_Categorie_FR' => $quiz->getActionCategorieFR(),
                'Action_Categorie_EN' => $quiz->getActionCategorieEN()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{IDModule}', name: 'api_quiz_update', methods: ['PUT'])]
    public function update(Request $request, string $IDModule, QuizRepository $quizRepository): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule
            $quizzes = $quizRepository->findBy(['IDModule' => $IDModule]);
            if (empty($quizzes)) {
                return $this->json(['message' => 'Quiz not found'], 404);
            }

            $data = json_decode($request->getContent(), true);
            $firstQuiz = $quizzes[0]; // Pour retourner les informations dans la réponse

            // Mettre à jour tous les quiz avec le même IDModule
            foreach ($quizzes as $quiz) {
                if (isset($data['Nom_FR'])) {
                    $quiz->setNomFR($data['Nom_FR']);
                }

                if (isset($data['Nom_EN'])) {
                    $quiz->setNomEN($data['Nom_EN']);
                }

                if (isset($data['Type'])) {
                    $quiz->setType($data['Type']);
                }

                if (isset($data['Category'])) {
                    $quiz->setCategory($data['Category']);
                }

                if (isset($data['MainSurface'])) {
                    $quiz->setMainSurface($data['MainSurface']);
                }

                // Mettre à jour Main et Surface pour tous les quiz
                if (isset($data['Main'])) {
                    $quiz->setMain($data['Main']);
                }

                if (isset($data['Surface'])) {
                    $quiz->setSurface($data['Surface']);
                }

                $errors = $this->validator->validate($quiz);
                if (count($errors) > 0) {
                    return $this->json(['errors' => (string) $errors], 400);
                }
            }

            $this->em->flush();

            return $this->json([
                'message' => 'Quiz updated successfully',
                'quiz' => [
                    'id' => $firstQuiz->getId(),
                    'IDModule' => $firstQuiz->getIDModule(),
                    'Nom_FR' => $firstQuiz->getNomFR(),
                    'Nom_EN' => $firstQuiz->getNomEN(),
                    'Type' => $firstQuiz->getType(),
                    'Category' => $firstQuiz->getCategory(),
                    'MainSurface' => $firstQuiz->isMainSurface(),
                    'Main' => $firstQuiz->getMain(),
                    'Surface' => $firstQuiz->getSurface()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_quiz_delete', methods: ['DELETE'])]
    public function delete(int $id, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $quiz = $quizRepository->find($id);
            if (!$quiz) {
                return $this->json(['message' => 'Quiz not found'], 404);
            }

            $this->em->remove($quiz);
            $this->em->flush();

            return $this->json(['message' => 'Quiz deleted successfully']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/by-idmodule/{IDModule}', name: 'api_quiz_delete_by_idmodule', methods: ['DELETE'])]
    public function deleteByIdModule(string $IDModule, QuizRepository $quizRepository): JsonResponse
    {
        $quizzes = $quizRepository->findBy(['IDModule' => $IDModule]);
        if (!$quizzes || count($quizzes) === 0) {
            return $this->json(['message' => 'Quiz not found'], 404);
        }
        foreach ($quizzes as $quiz) {
            $this->em->remove($quiz);
        }
        $this->em->flush();
        return $this->json(['message' => 'Quiz deleted successfully']);
    }

    #[Route('/competence/{IDModule}/{competenceId}', name: 'api_competence_delete', methods: ['DELETE'])]
    public function deleteCompetence(string $IDModule, string $competenceId, QuizRepository $quizRepository): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule et ce Competence_ID
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Compétence non trouvée'], 404);
            }

            // Supprimer tous les quiz associés à cette compétence
            foreach ($quizzes as $quiz) {
                $this->em->remove($quiz);
            }

            $this->em->flush();

            return $this->json(['message' => 'Compétence supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sous-competence/{IDModule}/{competenceId}/{sousCompetenceNomFR}/{sousCompetenceNomEN}', name: 'api_sous_competence_delete', methods: ['DELETE'])]
    public function deleteSousCompetence(
        string $IDModule,
        string $competenceId,
        string $sousCompetenceNomFR,
        string $sousCompetenceNomEN,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule, ce Competence_ID et ces noms de sous-compétence
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId,
                'SousCompetence_Nom_FR' => $sousCompetenceNomFR,
                'SousCompetence_Nom_EN' => $sousCompetenceNomEN
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Sous-compétence non trouvée'], 404);
            }

            // Supprimer tous les quiz associés à cette sous-compétence
            foreach ($quizzes as $quiz) {
                $this->em->remove($quiz);
            }

            $this->em->flush();

            return $this->json(['message' => 'Sous-compétence supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/action/{IDModule}/{competenceId}/{actionNomFR}/{actionNomEN}', name: 'api_action_delete', methods: ['DELETE'])]
    public function deleteAction(
        string $IDModule,
        string $competenceId,
        string $actionNomFR,
        string $actionNomEN,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule, ce Competence_ID et ces noms d'action
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId,
                'Action_Nom_FR' => $actionNomFR,
                'Action_Nom_EN' => $actionNomEN
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            // Supprimer tous les quiz associés à cette action
            foreach ($quizzes as $quiz) {
                $this->em->remove($quiz);
            }

            $this->em->flush();

            return $this->json(['message' => 'Action supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/competence/{IDModule}/{competenceId}', name: 'api_competence_update', methods: ['PUT'])]
    public function updateCompetence(
        Request $request,
        string $IDModule,
        string $competenceId,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule et ce Competence_ID
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Compétence non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour tous les quiz associés à cette compétence
            foreach ($quizzes as $quiz) {
                if (isset($data['Competence_Nom_FR'])) {
                    $quiz->setCompetenceNomFR($data['Competence_Nom_FR']);
                }
                if (isset($data['Competence_Nom_EN'])) {
                    $quiz->setCompetenceNomEN($data['Competence_Nom_EN']);
                }
                if (isset($data['Comp_Categorie_FR'])) {
                    $quiz->setCompCategorieFR($data['Comp_Categorie_FR']);
                }
                if (isset($data['Comp_Categorie_EN'])) {
                    $quiz->setCompCategorieEN($data['Comp_Categorie_EN']);
                }
            }

            $this->em->flush();

            return $this->json(['message' => 'Compétence mise à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sous-competence/{IDModule}/{competenceId}/{sousCompetenceNomFR}/{sousCompetenceNomEN}', name: 'api_sous_competence_update', methods: ['PUT'])]
    public function updateSousCompetence(
        Request $request,
        string $IDModule,
        string $competenceId,
        string $sousCompetenceNomFR,
        string $sousCompetenceNomEN,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Décoder les noms de sous-compétence
            $decodedNomFR = urldecode($sousCompetenceNomFR);
            $decodedNomEN = urldecode($sousCompetenceNomEN);

            // Récupérer les données de la requête
            $data = json_decode($request->getContent(), true);

            // Trouver tous les quiz avec cet IDModule, ce Competence_ID et ces noms de sous-compétence
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId,
                'SousCompetence_Nom_FR' => $decodedNomFR,
                'SousCompetence_Nom_EN' => $decodedNomEN
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Sous-compétence non trouvée'], 404);
            }

            // Mettre à jour tous les quiz trouvés
            foreach ($quizzes as $quiz) {
                $quiz->setSousCompetenceNomFR($data['SousCompetence_Nom_FR']);
                $quiz->setSousCompetenceNomEN($data['SousCompetence_Nom_EN']);
            }

            // Enregistrer les modifications
            $this->em->flush();

            // Compter le nombre de quiz mis à jour
            $rowCount = count($quizzes);

            if ($rowCount === 0) {
                return $this->json(['message' => 'Aucune sous-compétence n\'a été mise à jour'], 404);
            }

            return $this->json([
                'message' => 'Sous-compétence mise à jour avec succès',
                'updated_rows' => $rowCount
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sous-competence-by-id/{IDModule}/{competenceId}/{id}', name: 'api_sous_competence_update_by_id', methods: ['PUT'])]
    public function updateSousCompetenceById(
        Request $request,
        string $IDModule,
        string $competenceId,
        string $id,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {


            // Trouver tous les quiz avec cet IDModule et ce Competence_ID
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId
            ]);

            if (empty($quizzes)) {

                return $this->json(['message' => 'Aucun quiz trouvé pour cet IDModule et cette compétence'], 404);
            }

            // Trouver la sous-compétence par son ID dans les quizzes
            $targetQuiz = null;
            foreach ($quizzes as $q) {

                // L'ID peut être stocké dans un champ personnalisé ou être l'ID réel du quiz
                if ($q->getId() == $id) {
                    $targetQuiz = $q;

                    break;
                }
            }

            if (!$targetQuiz) {

                return $this->json(['message' => 'Sous-compétence non trouvée avec ID ' . $id], 404);
            }

            $data = json_decode($request->getContent(), true);


            // Trouver tous les quiz avec le même IDModule, competenceId et les mêmes noms de sous-compétence
            $matchingQuizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId,
                'SousCompetence_Nom_FR' => $targetQuiz->getSousCompetenceNomFR(),
                'SousCompetence_Nom_EN' => $targetQuiz->getSousCompetenceNomEN()
            ]);



            // Mettre à jour tous les quiz associés à cette sous-compétence
            foreach ($matchingQuizzes as $q) {

                if (isset($data['SousCompetence_Nom_FR'])) {
                    $q->setSousCompetenceNomFR($data['SousCompetence_Nom_FR']);
                }
                if (isset($data['SousCompetence_Nom_EN'])) {
                    $q->setSousCompetenceNomEN($data['SousCompetence_Nom_EN']);
                }
            }

            $this->em->flush();

            return $this->json(['message' => 'Sous-compétence mise à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/action/{IDModule}/{competenceId}/{actionNomFR}/{actionNomEN}', name: 'api_action_update', methods: ['PUT'])]
    public function updateAction(
        Request $request,
        string $IDModule,
        string $competenceId,
        string $actionNomFR,
        string $actionNomEN,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule, ce Competence_ID et ces noms d'action
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => $competenceId,
                'Action_Nom_FR' => $actionNomFR,
                'Action_Nom_EN' => $actionNomEN
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour tous les quiz associés à cette action
            foreach ($quizzes as $quiz) {
                if (isset($data['Action_Nom_FR'])) {
                    $quiz->setActionNomFR($data['Action_Nom_FR']);
                }
                if (isset($data['Action_Nom_EN'])) {
                    $quiz->setActionNomEN($data['Action_Nom_EN']);
                }
                if (isset($data['Action_Categorie_FR'])) {
                    $quiz->setActionCategorieFR($data['Action_Categorie_FR']);
                }
                if (isset($data['Action_Categorie_EN'])) {
                    $quiz->setActionCategorieEN($data['Action_Categorie_EN']);
                }
            }

            $this->em->flush();

            return $this->json(['message' => 'Action mise à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/competence/create', name: 'api_competence_create', methods: ['POST'])]
    public function createCompetence(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);



            // Validation des champs requis
            $requiredFields = ['IDModule', 'Competence_ID', 'Competence_Nom_FR', 'Competence_Nom_EN'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {

                return $this->json([
                    'error' => 'Champs requis manquants',
                    'missing_fields' => $missingFields
                ], 400);
            }

            // Vérifier et convertir Competence_ID en entier
            $competenceId = null;
            if (is_string($data['Competence_ID'])) {
                $competenceId = (int)$data['Competence_ID'];

            } else if (is_int($data['Competence_ID'])) {
                $competenceId = $data['Competence_ID'];

            } else {

                return $this->json([
                    'error' => 'Competence_ID doit être un entier ou une chaîne convertible en entier',
                    'type_reçu' => gettype($data['Competence_ID'])
                ], 400);
            }

            // Vérifier si la compétence existe déjà
            $existingQuiz = $this->em->getRepository(Quiz::class)->findOneBy([
                'IDModule' => $data['IDModule'],
                'Competence_ID' => $competenceId
            ]);

            if ($existingQuiz) {

                return $this->json([
                    'error' => 'Cette compétence existe déjà pour ce quiz',
                    'competence_id' => $competenceId,
                    'id_module' => $data['IDModule']
                ], 409); // Conflict
            }

            // Trouver le cours associé à ce quiz
            $quizzes = $this->em->getRepository(Quiz::class)->findBy(['IDModule' => $data['IDModule']]);
            if (empty($quizzes)) {

                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule',
                    'id_module' => $data['IDModule']
                ], 404);
            }
            $cours = $quizzes[0]->getCours();


            // Créer un nouveau quiz avec la compétence
            $quiz = new Quiz();

            try {
                // Définir les propriétés du quiz
                $quiz->setCours($cours);
                $quiz->setIDModule($data['IDModule']);
                $quiz->setType($data['Type'] ?? 'Evaluation');
                $quiz->setCategory($data['Category'] ?? 'Sterile');
                $quiz->setMainSurface($data['MainSurface'] ?? false);
                $quiz->setSurface($data['Surface'] ?? 0);
                $quiz->setMain($data['Main'] ?? 0);

                // Nom du quiz
                if (empty($data['Nom_FR'])) {
                    $quiz->setNomFR($quizzes[0]->getNomFR());
                } else {
                    $quiz->setNomFR($data['Nom_FR']);
                }

                if (empty($data['Nom_EN'])) {
                    $quiz->setNomEN($quizzes[0]->getNomEN());
                } else {
                    $quiz->setNomEN($data['Nom_EN']);
                }

                // Définir Competence_ID
                $quiz->setCompetenceID($competenceId);

                // Définir les noms de compétence
                $quiz->setCompetenceNomFR($data['Competence_Nom_FR']);
                $quiz->setCompetenceNomEN($data['Competence_Nom_EN']);

                // Définir les catégories de compétence (optionnelles)
                $quiz->setCompCategorieFR($data['Comp_Categorie_FR'] ?? '');
                $quiz->setCompCategorieEN($data['Comp_Categorie_EN'] ?? '');

                // Pas de sous-compétence ou d'action pour une nouvelle compétence
                $quiz->setSousCompetenceNomFR('');
                $quiz->setSousCompetenceNomEN('');
                $quiz->setActionNomFR('');
                $quiz->setActionNomEN('');
                $quiz->setActionCategorieFR('');
                $quiz->setActionCategorieEN('');


            } catch (\Exception $e) {

                return $this->json([
                    'error' => 'Erreur lors de la création du quiz',
                    'message' => $e->getMessage()
                ], 400);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }

                return $this->json([
                    'error' => 'Validation failed',
                    'messages' => $errorMessages
                ], 400);
            }

            // Persister l'entité
            try {
                error_log('[DEBUG] Début de la persistance du quiz');

                // Vérifier l'état de l'EntityManager
                if (!$this->em->isOpen()) {
                    error_log('[ERREUR] EntityManager est fermé!');
                    $this->em = $this->em->create(
                        $this->em->getConnection(),
                        $this->em->getConfiguration()
                    );
                    error_log('[DEBUG] EntityManager recréé');
                }

                // Persister l'entité
                $this->em->persist($quiz);
                error_log('[DEBUG] Quiz persisté, avant flush');

                // Flush pour enregistrer en base de données
                $this->em->flush();
                error_log('[DEBUG] Flush réussi - Quiz ID: ' . $quiz->getId());

                // Vérifier que l'ID a été généré
                if (!$quiz->getId()) {
                    error_log('[ERREUR] Quiz persisté mais aucun ID généré!');
                } else {
                    error_log('[DEBUG] Quiz persisté avec succès avec ID: ' . $quiz->getId());
                }

                // Vérifier dans la base de données
                $connection = $this->em->getConnection();
                $stmt = $connection->prepare('SELECT id FROM quiz WHERE id = :id');
                $stmt->bindValue('id', $quiz->getId());
                $result = $stmt->executeQuery();
                $dbRecord = $result->fetchAssociative();

                if ($dbRecord) {
                    error_log('[DEBUG] Vérification en base de données: Enregistrement trouvé avec ID ' . $dbRecord['id']);
                } else {
                    error_log('[ERREUR] Vérification en base de données: Aucun enregistrement trouvé avec ID ' . $quiz->getId());
                }

            } catch (\Exception $e) {
                error_log('[ERREUR] Exception lors de la persistance: ' . $e->getMessage());

                // Vérifier si c'est une erreur de duplication
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    error_log('[ERREUR] Erreur de duplication détectée');
                    return $this->json([
                        'error' => 'Cette compétence existe déjà (erreur de duplication)',
                        'message' => $e->getMessage()
                    ], 409);
                }

                return $this->json([
                    'error' => 'Erreur lors de la persistance du quiz',
                    'message' => $e->getMessage()
                ], 500);
            }

            // Retourner la réponse
            error_log('[DEBUG] Fin de createCompetence - Succès');
            return $this->json([
                'message' => 'Compétence créée avec succès',
                'quiz_id' => $quiz->getId(),
                'competence' => [
                    'Competence_ID' => $quiz->getCompetenceID(),
                    'Competence_Nom_FR' => $quiz->getCompetenceNomFR(),
                    'Competence_Nom_EN' => $quiz->getCompetenceNomEN()
                ]
            ], 201);
        } catch (\Exception $e) {
            error_log('[ERREUR] Exception globale dans createCompetence: ' . $e->getMessage());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sous-competence/create', name: 'api_sous_competence_create', methods: ['POST'])]
    public function createSousCompetence(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Log des données reçues
            error_log('Données reçues pour createSousCompetence: ' . print_r($data, true));

            // Validation des champs requis
            $requiredFields = ['IDModule', 'Competence_ID', 'Competence_Nom_FR', 'Competence_Nom_EN', 'SousCompetence_Nom_FR', 'SousCompetence_Nom_EN'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                error_log('Champs requis manquants: ' . implode(', ', $missingFields));
                return $this->json([
                    'error' => 'Champs requis manquants',
                    'missing_fields' => $missingFields
                ], 400);
            }

            // Vérifier si la sous-compétence existe déjà
            $competenceId = is_string($data['Competence_ID']) ? (int)$data['Competence_ID'] : $data['Competence_ID'];
            $existingQuiz = $this->em->getRepository(Quiz::class)->findOneBy([
                'IDModule' => $data['IDModule'],
                'Competence_ID' => $competenceId,
                'SousCompetence_Nom_FR' => $data['SousCompetence_Nom_FR'],
                'SousCompetence_Nom_EN' => $data['SousCompetence_Nom_EN']
            ]);

            if ($existingQuiz) {
                error_log('Sous-compétence existante: ' . $data['SousCompetence_Nom_FR']);
                return $this->json([
                    'error' => 'Cette sous-compétence existe déjà pour cette compétence'
                ], 409); // Conflict
            }

            // Trouver le cours associé à ce quiz
            $quizzes = $this->em->getRepository(Quiz::class)->findBy(['IDModule' => $data['IDModule']]);
            if (empty($quizzes)) {
                error_log('Aucun quiz trouvé avec IDModule: ' . $data['IDModule']);
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }
            $cours = $quizzes[0]->getCours();
            error_log('Cours trouvé: ' . $cours->getId());

            try {
                // Créer un nouveau quiz avec la sous-compétence
                $quiz = new Quiz();
                $quiz->setCours($cours);
                $quiz->setIDModule($data['IDModule']);
                $quiz->setType($data['Type'] ?? 'Evaluation');
                $quiz->setCategory($data['Category'] ?? 'Sterile');
                $quiz->setMainSurface($data['MainSurface'] ?? false);
                $quiz->setSurface($data['Surface'] ?? 0);
                $quiz->setMain($data['Main'] ?? 0);
                $quiz->setNomFR($data['Nom_FR'] ?? $quizzes[0]->getNomFR());
                $quiz->setNomEN($data['Nom_EN'] ?? $quizzes[0]->getNomEN());
                // Convertir Competence_ID en entier si c'est une chaîne
                $quiz->setCompetenceID(is_string($data['Competence_ID']) ? (int)$data['Competence_ID'] : $data['Competence_ID']);
                $quiz->setCompetenceNomFR($data['Competence_Nom_FR']);
                $quiz->setCompetenceNomEN($data['Competence_Nom_EN']);
                $quiz->setCompCategorieFR($data['Comp_Categorie_FR'] ?? '');
                $quiz->setCompCategorieEN($data['Comp_Categorie_EN'] ?? '');
                $quiz->setSousCompetenceNomFR($data['SousCompetence_Nom_FR']);
                $quiz->setSousCompetenceNomEN($data['SousCompetence_Nom_EN']);
                // Pas d'action pour une nouvelle sous-compétence
                $quiz->setActionNomFR('');
                $quiz->setActionNomEN('');
                $quiz->setActionCategorieFR('');
                $quiz->setActionCategorieEN('');

                error_log('Quiz créé avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la création du quiz: ' . $e->getMessage());
                throw $e;
            }

            // Validation de l'entité
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                error_log('Erreurs de validation: ' . implode(', ', $errorMessages));
                return $this->json([
                    'error' => 'Validation failed',
                    'messages' => $errorMessages
                ], 400);
            }

            try {
                $this->em->persist($quiz);
                $this->em->flush();
                error_log('Quiz persisté avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance du quiz: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
                return $this->json([
                    'error' => 'Erreur lors de la persistance du quiz',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return $this->json([
                'message' => 'Sous-compétence créée avec succès',
                'quiz_id' => $quiz->getId(),
                'sous_competence' => [
                    'SousCompetence_Nom_FR' => $quiz->getSousCompetenceNomFR(),
                    'SousCompetence_Nom_EN' => $quiz->getSousCompetenceNomEN()
                ]
            ], 201);
        } catch (\Exception $e) {
            error_log('Exception dans createSousCompetence: ' . $e->getMessage());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/action/create', name: 'api_action_create', methods: ['POST'])]
    public function createAction(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des champs requis
            $requiredFields = ['IDModule', 'Competence_ID', 'Competence_Nom_FR', 'Competence_Nom_EN', 'Action_Nom_FR', 'Action_Nom_EN'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->json([
                    'error' => 'Champs requis manquants',
                    'missing_fields' => $missingFields
                ], 400);
            }

            // Vérifier si le quiz existe déjà
            $competenceId = is_string($data['Competence_ID']) ? (int)$data['Competence_ID'] : $data['Competence_ID'];
            $existingQuiz = $this->em->getRepository(Quiz::class)->findOneBy([
                'IDModule' => $data['IDModule'],
                'Competence_ID' => $competenceId,
                'Action_Nom_FR' => $data['Action_Nom_FR'],
                'Action_Nom_EN' => $data['Action_Nom_EN']
            ]);

            if ($existingQuiz) {
                return $this->json([
                    'error' => 'Cette action existe déjà pour cette compétence'
                ], 409); // Conflict
            }

            // Trouver le cours associé à ce quiz
            $quizzes = $this->em->getRepository(Quiz::class)->findBy(['IDModule' => $data['IDModule']]);
            if (empty($quizzes)) {
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }
            $cours = $quizzes[0]->getCours();

            // Créer un nouveau quiz avec l'action
            $quiz = new Quiz();
            $quiz->setCours($cours);
            $quiz->setIDModule($data['IDModule']);
            $quiz->setType($data['Type'] ?? 'Evaluation');
            $quiz->setCategory($data['Category'] ?? 'Sterile');
            $quiz->setMainSurface($data['MainSurface'] ?? false);
            $quiz->setSurface($data['Surface'] ?? 0);
            $quiz->setMain($data['Main'] ?? 0);
            $quiz->setNomFR($data['Nom_FR'] ?? $quizzes[0]->getNomFR());
            $quiz->setNomEN($data['Nom_EN'] ?? $quizzes[0]->getNomEN());
            // Convertir Competence_ID en entier si c'est une chaîne
            $quiz->setCompetenceID(is_string($data['Competence_ID']) ? (int)$data['Competence_ID'] : $data['Competence_ID']);
            $quiz->setCompetenceNomFR($data['Competence_Nom_FR']);
            $quiz->setCompetenceNomEN($data['Competence_Nom_EN']);
            $quiz->setCompCategorieFR($data['Comp_Categorie_FR'] ?? '');
            $quiz->setCompCategorieEN($data['Comp_Categorie_EN'] ?? '');
            $quiz->setSousCompetenceNomFR($data['SousCompetence_Nom_FR'] ?? '');
            $quiz->setSousCompetenceNomEN($data['SousCompetence_Nom_EN'] ?? '');
            $quiz->setActionNomFR($data['Action_Nom_FR']);
            $quiz->setActionNomEN($data['Action_Nom_EN']);
            $quiz->setActionCategorieFR($data['Action_Categorie_FR'] ?? '');
            $quiz->setActionCategorieEN($data['Action_Categorie_EN'] ?? '');

            // Validation de l'entité
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                return $this->json([
                    'error' => 'Validation failed',
                    'messages' => $errorMessages
                ], 400);
            }

            try {
                $this->em->persist($quiz);
                $this->em->flush();
                error_log('Quiz persisté avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance du quiz: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
                return $this->json([
                    'error' => 'Erreur lors de la persistance du quiz',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return $this->json([
                'message' => 'Action créée avec succès',
                'quiz_id' => $quiz->getId(),
                'action' => [
                    'Action_Nom_FR' => $quiz->getActionNomFR(),
                    'Action_Nom_EN' => $quiz->getActionNomEN(),
                    'Action_Categorie_FR' => $quiz->getActionCategorieFR(),
                    'Action_Categorie_EN' => $quiz->getActionCategorieEN()
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz-action/create', name: 'api_quiz_action_create', methods: ['POST'])]
    public function createQuizAction(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des champs requis
            $requiredFields = ['IDModule', 'Action_Nom_FR', 'Action_Nom_EN'];
            $missingFields = [];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || (is_string($data[$field]) && empty($data[$field]))) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                return $this->json([
                    'error' => 'Champs requis manquants',
                    'missing_fields' => $missingFields
                ], 400);
            }

            // Vérifier si l'action existe déjà pour ce quiz
            error_log('Vérification de l\'existence d\'une action: IDModule=' . $data['IDModule'] . ', Action_Nom_FR=' . $data['Action_Nom_FR'] . ', Action_Nom_EN=' . $data['Action_Nom_EN']);

            $existingQuiz = $this->em->getRepository(Quiz::class)->findOneBy([
                'IDModule' => $data['IDModule'],
                'Competence_ID' => 0, // Action au niveau du quiz, pas de compétence associée (utilise 0 au lieu de null)
                'Action_Nom_FR' => $data['Action_Nom_FR'],
                'Action_Nom_EN' => $data['Action_Nom_EN']
            ]);

            if ($existingQuiz) {
                error_log('Action existante trouvée avec ID: ' . $existingQuiz->getId());
                return $this->json([
                    'error' => 'Cette action existe déjà pour ce quiz',
                    'existing_id' => $existingQuiz->getId()
                ], 409); // Conflict
            }

            error_log('Aucune action existante trouvée, création d\'une nouvelle action');

            // Trouver le cours associé à ce quiz
            $quizzes = $this->em->getRepository(Quiz::class)->findBy(['IDModule' => $data['IDModule']]);
            if (empty($quizzes)) {
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }
            $cours = $quizzes[0]->getCours();

            // Créer un nouveau quiz avec l'action au niveau du quiz
            $quiz = new Quiz();
            $quiz->setCours($cours);
            $quiz->setIDModule($data['IDModule']);
            $quiz->setType($data['Type'] ?? 'Formation');
            $quiz->setCategory($data['Category'] ?? 'Sterile');
            $quiz->setMainSurface($data['MainSurface'] ?? false);
            $quiz->setSurface($data['Surface'] ?? 0);
            $quiz->setMain($data['Main'] ?? 0);
            $quiz->setNomFR($data['Nom_FR'] ?? $quizzes[0]->getNomFR());
            $quiz->setNomEN($data['Nom_EN'] ?? $quizzes[0]->getNomEN());

            // Pas de compétence pour une action au niveau du quiz (utiliser 0 au lieu de null)
            $quiz->setCompetenceID(0);
            $quiz->setCompetenceNomFR('');
            $quiz->setCompetenceNomEN('');
            $quiz->setCompCategorieFR('');
            $quiz->setCompCategorieEN('');

            // Pas de sous-compétence
            $quiz->setSousCompetenceNomFR('');
            $quiz->setSousCompetenceNomEN('');

            // Définir les valeurs de l'action
            $quiz->setActionNomFR($data['Action_Nom_FR']);
            $quiz->setActionNomEN($data['Action_Nom_EN']);
            $quiz->setActionCategorieFR($data['Action_Categorie_FR'] ?? '');
            $quiz->setActionCategorieEN($data['Action_Categorie_EN'] ?? '');

            // Validation de l'entité
            $errors = $this->validator->validate($quiz);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
                }
                return $this->json([
                    'error' => 'Validation failed',
                    'messages' => $errorMessages
                ], 400);
            }

            try {
                $this->em->persist($quiz);
                $this->em->flush();
                error_log('Quiz action persistée avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance de l\'action du quiz: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
                return $this->json([
                    'error' => 'Erreur lors de la persistance de l\'action',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return $this->json([
                'message' => 'Action au niveau du quiz créée avec succès',
                'quiz_id' => $quiz->getId(),
                'action' => [
                    'id' => $quiz->getId(),
                    'Action_Nom_FR' => $quiz->getActionNomFR(),
                    'Action_Nom_EN' => $quiz->getActionNomEN(),
                    'Action_Categorie_FR' => $quiz->getActionCategorieFR(),
                    'Action_Categorie_EN' => $quiz->getActionCategorieEN()
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz-action-by-id/{id}', name: 'api_quiz_action_delete_by_id', methods: ['DELETE'])]
    public function deleteQuizActionById(
        int $id,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver le quiz par son ID
            $quiz = $quizRepository->find($id);

            if (!$quiz) {
                return $this->json(['message' => 'Action non trouvée avec ID ' . $id], 404);
            }

            // Supprimer le quiz
            $this->em->remove($quiz);
            $this->em->flush();

            return $this->json(['message' => 'Action supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz-action/{IDModule}/{actionNomFR}/{actionNomEN}', name: 'api_quiz_action_delete', methods: ['DELETE'])]
    public function deleteQuizAction(
        string $IDModule,
        string $actionNomFR,
        string $actionNomEN,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule et ces noms d'action (sans compétence associée)
            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => 0, // Action au niveau du quiz (utilise 0 au lieu de null)
                'Action_Nom_FR' => $actionNomFR,
                'Action_Nom_EN' => $actionNomEN
            ]);

            if (empty($quizzes)) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            // Supprimer tous les quiz associés à cette action
            foreach ($quizzes as $quiz) {
                $this->em->remove($quiz);
            }

            $this->em->flush();

            return $this->json(['message' => 'Action supprimée avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz-action-by-id/{id}', name: 'api_quiz_action_update_by_id', methods: ['PUT'])]
    public function updateQuizActionById(
        Request $request,
        int $id,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver le quiz par son ID
            $quiz = $quizRepository->find($id);

            if (!$quiz) {
                return $this->json(['message' => 'Action non trouvée avec ID ' . $id], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour l'action
            if (isset($data['Action_Nom_FR'])) {
                $quiz->setActionNomFR($data['Action_Nom_FR']);
            }
            if (isset($data['Action_Nom_EN'])) {
                $quiz->setActionNomEN($data['Action_Nom_EN']);
            }
            if (isset($data['Action_Categorie_FR'])) {
                $quiz->setActionCategorieFR($data['Action_Categorie_FR']);
            }
            if (isset($data['Action_Categorie_EN'])) {
                $quiz->setActionCategorieEN($data['Action_Categorie_EN']);
            }

            $this->em->flush();

            return $this->json(['message' => 'Action mise à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz-action/{IDModule}/{actionNomFR}/{actionNomEN}', name: 'api_quiz_action_update', methods: ['PUT'])]
    public function updateQuizAction(
        Request $request,
        string $IDModule,
        string $actionNomFR,
        string $actionNomEN,
        QuizRepository $quizRepository
    ): JsonResponse
    {
        try {
            // Trouver tous les quiz avec cet IDModule et ces noms d'action (sans compétence associée)
            error_log('Recherche d\'action pour mise à jour: IDModule=' . $IDModule . ', Action_Nom_FR=' . $actionNomFR . ', Action_Nom_EN=' . $actionNomEN);

            $quizzes = $quizRepository->findBy([
                'IDModule' => $IDModule,
                'Competence_ID' => 0, // Action au niveau du quiz (utilise 0 au lieu de null)
                'Action_Nom_FR' => $actionNomFR,
                'Action_Nom_EN' => $actionNomEN
            ]);

            if (empty($quizzes)) {
                error_log('Aucune action trouvée avec ces critères');

                // Recherche plus large pour le débogage
                $allQuizzes = $quizRepository->findBy(['IDModule' => $IDModule, 'Competence_ID' => 0]);
                error_log('Nombre total d\'actions pour ce quiz: ' . count($allQuizzes));
                foreach ($allQuizzes as $q) {
                    error_log('Action trouvée: FR=' . $q->getActionNomFR() . ', EN=' . $q->getActionNomEN());
                }

                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour tous les quiz associés à cette action
            foreach ($quizzes as $quiz) {
                if (isset($data['Action_Nom_FR'])) {
                    $quiz->setActionNomFR($data['Action_Nom_FR']);
                }
                if (isset($data['Action_Nom_EN'])) {
                    $quiz->setActionNomEN($data['Action_Nom_EN']);
                }
                if (isset($data['Action_Categorie_FR'])) {
                    $quiz->setActionCategorieFR($data['Action_Categorie_FR']);
                }
                if (isset($data['Action_Categorie_EN'])) {
                    $quiz->setActionCategorieEN($data['Action_Categorie_EN']);
                }
            }

            $this->em->flush();

            return $this->json(['message' => 'Action mise à jour avec succès']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}