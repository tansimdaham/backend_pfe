<?php

namespace App\Controller;

use App\Entity\Action;
use App\Entity\Competence;
use App\Entity\Quiz;
use App\Entity\SousCompetence;
use App\Repository\ActionRepository;
use App\Repository\CompetenceRepository;
use App\Repository\CoursRepository;
use App\Repository\QuizRepository;
use App\Repository\SousCompetenceRepository;
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
            // Add detailed logging for debugging
            error_log("QuizController::createBatch - Starting batch processing");
            error_log("QuizController::createBatch - Raw request content: " . $request->getContent());

            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("QuizController::createBatch - JSON decode error: " . json_last_error_msg());
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid JSON data: ' . json_last_error_msg(),
                ], 400);
            }

            if (!is_array($data)) {
                error_log("QuizController::createBatch - Data is not an array: " . gettype($data));
                return $this->json([
                    'status' => 'error',
                    'message' => 'Invalid data format: expected JSON array',
                ], 400);
            }

            // Debug log to see what data is being received
            error_log("QuizController::createBatch - Received data count: " . count($data));
            // Log the first item for debugging
            if (count($data) > 0) {
                error_log("QuizController::createBatch - First item: " . print_r($data[0], true));
                // Check if IDModule exists in the first item
                if (isset($data[0]['IDModule'])) {
                    error_log("QuizController::createBatch - First item has IDModule: " . $data[0]['IDModule']);
                } else {
                    error_log("QuizController::createBatch - First item does NOT have IDModule key");
                    // Check all keys in the first item
                    error_log("QuizController::createBatch - First item keys: " . implode(', ', array_keys($data[0])));
                }
            }

            $quizRepository = $this->em->getRepository(Quiz::class);
            $results = [];
            $successCount = 0;
            $errorCount = 0;

            // Regrouper les éléments par IDModule pour éviter les violations de contrainte unique
            $groupedItems = [];
            foreach ($data as $index => $item) {
                // Debug log to see each item
                error_log("QuizController::createBatch - Processing item at index $index: " . print_r($item, true));

                // Ensure IDModule exists and is standardized
                $idModule = null;

                // First check if IDModule exists directly
                if (isset($item['IDModule'])) {
                    $idModule = $item['IDModule'];
                    error_log("QuizController::createBatch - Found IDModule directly: $idModule");
                } else if (isset($item['idmodule'])) {
                    // Check for lowercase version
                    $idModule = $item['idmodule'];
                    error_log("QuizController::createBatch - Found idmodule (lowercase): $idModule");
                } else if (isset($item['Idmodule'])) {
                    // Check for mixed case version
                    $idModule = $item['Idmodule'];
                    error_log("QuizController::createBatch - Found Idmodule (mixed case): $idModule");
                } else {
                    // If not, try case-insensitive check for all keys
                    foreach ($item as $key => $value) {
                        if (strtolower($key) === 'idmodule') {
                            $idModule = $value;
                            error_log("QuizController::createBatch - Found IDModule with case-insensitive check: $idModule");
                            break;
                        }
                    }

                    // If still not found, log all keys for debugging
                    if ($idModule === null) {
                        error_log("QuizController::createBatch - IDModule not found in item. Available keys: " . implode(', ', array_keys($item)));
                    }
                }

                // Ensure idModule is not null or empty
                if (empty($idModule)) {
                    error_log("QuizController::createBatch - IDModule is empty or null for item at index $index");
                    $idModule = 'default_' . uniqid();
                    error_log("QuizController::createBatch - Using default IDModule: $idModule");
                }

                if ($idModule !== null) {
                    if (!isset($groupedItems[$idModule])) {
                        $groupedItems[$idModule] = [];
                    }
                    $item['original_index'] = $index;
                    // Ensure IDModule key is standardized with correct case
                    $item['IDModule'] = $idModule;
                    $groupedItems[$idModule][] = $item;
                } else {
                    // Traiter les éléments sans IDModule individuellement
                    error_log("QuizController::createBatch - Missing IDModule for item at index $index");
                    $result = ['index' => $index];
                    $result['success'] = false;
                    $result['error'] = "IDModule manquant";
                    $results[] = $result;
                    $errorCount++;
                }
            }

            // Traiter chaque groupe d'IDModule
            foreach ($groupedItems as $idModule => $items) {
                // Vérifier si un quiz avec cet IDModule existe déjà
                $existingQuiz = null;
                $cours = null;

                // Utiliser le premier élément pour vérifier l'existence du cours et du quiz
                $firstItem = $items[0];
                $result = ['index' => $firstItem['original_index']];

                try {
                    // Validation des champs requis
                    $requiredFields = ['cours', 'IDModule', 'Nom_FR', 'Nom_EN'];
                    $missingFields = [];
                    foreach ($requiredFields as $field) {
                        // Special handling for IDModule - check case variations
                        if ($field === 'IDModule') {
                            if (
                                (!isset($firstItem['IDModule']) || empty($firstItem['IDModule'])) &&
                                (!isset($firstItem['idmodule']) || empty($firstItem['idmodule'])) &&
                                (!isset($firstItem['Idmodule']) || empty($firstItem['Idmodule']))
                            ) {
                                $missingFields[] = $field;
                                error_log("QuizController::createBatch - Required field missing: $field");
                            }
                        } else if (!isset($firstItem[$field]) || empty($firstItem[$field])) {
                            $missingFields[] = $field;
                            error_log("QuizController::createBatch - Required field missing: $field");
                        }
                    }

                    // Log all available fields for debugging
                    error_log("QuizController::createBatch - Available fields in first item: " . implode(', ', array_keys($firstItem)));

                    if (!empty($missingFields)) {
                        throw new \InvalidArgumentException("Champs requis manquants: " . implode(', ', $missingFields));
                    }

                    // Vérification de l'existence du cours
                    $cours = $this->coursRepository->find($firstItem['cours']);
                    if (!$cours) {
                        throw new \InvalidArgumentException("Cours avec l'ID {$firstItem['cours']} non trouvé");
                    }

                    // Vérification des doublons
                    $existingQuiz = $quizRepository->findOneBy([
                        'IDModule' => $idModule
                    ]);

                    // Si un quiz avec cet IDModule existe déjà, on l'utilise au lieu d'en créer un nouveau
                    if ($existingQuiz) {
                        $quiz = $existingQuiz;
                    } else {
                        // Création d'un nouveau quiz
                        $quiz = new Quiz();
                        $quiz->setCours($cours);

                        // Ensure IDModule is set properly
                        try {
                            error_log("QuizController::createBatch - Setting IDModule for new quiz: " . $idModule);
                            $quiz->setIDModule($idModule);

                            // Verify that IDModule was set correctly
                            if (empty($quiz->getIDModule())) {
                                error_log("QuizController::createBatch - ERROR: IDModule is still empty after setIDModule");
                                // Force the value directly using reflection
                                $reflection = new \ReflectionClass($quiz);
                                $property = $reflection->getProperty('IDModule');
                                $property->setAccessible(true);
                                $property->setValue($quiz, $idModule);
                                error_log("QuizController::createBatch - IDModule set via reflection to: " . $idModule);
                            }
                        } catch (\Exception $ex) {
                            error_log("QuizController::createBatch - Exception setting IDModule: " . $ex->getMessage());
                            // Force the value directly using reflection
                            $reflection = new \ReflectionClass($quiz);
                            $property = $reflection->getProperty('IDModule');
                            $property->setAccessible(true);
                            $property->setValue($quiz, $idModule);
                            error_log("QuizController::createBatch - IDModule set via reflection to: " . $idModule);
                        }
                    }

                    // Mise à jour des propriétés du quiz (existant ou nouveau)
                    // Toujours utiliser 'Evaluation' comme type, quelle que soit la valeur reçue
                    $quiz->setType('Evaluation');
                    $quiz->setCategory($firstItem['Category'] ?? 'Sterile');
                    $quiz->setMainSurface($firstItem['MainSurface'] ?? false);
                    $quiz->setSurface($firstItem['Surface'] ?? 0);
                    $quiz->setMain($firstItem['Main'] ?? 0);
                    $quiz->setNomFR($firstItem['Nom_FR']);
                    $quiz->setNomEN($firstItem['Nom_EN']);

                    // Champs nullable simples
                    $simpleNullableFields = [
                        'PointFort_FR', 'PointFort_EN'
                    ];

                    foreach ($simpleNullableFields as $field) {
                        if (isset($firstItem[$field])) {
                            $setter = 'set' . str_replace('_', '', $field);
                            if (method_exists($quiz, $setter)) {
                                $quiz->$setter($firstItem[$field]);
                            }
                        }
                    }

                    // Traiter toutes les compétences et actions pour ce quiz
                    foreach ($items as $item) {
                        // Traitement des compétences
                        if (isset($item['Competence_Nom_FR']) && isset($item['Competence_Nom_EN'])) {
                            // Vérifier si cette compétence existe déjà pour ce quiz
                            $existingCompetence = null;

                            // Vérifier si la méthode getCompetences existe et si la collection n'est pas null
                            if (method_exists($quiz, 'getCompetences')) {
                                $competences = $quiz->getCompetences();
                                if ($competences !== null) {
                                    foreach ($competences as $comp) {
                                        if ($comp->getNomFr() === $item['Competence_Nom_FR'] &&
                                            $comp->getNomEn() === $item['Competence_Nom_EN']) {
                                            $existingCompetence = $comp;
                                            break;
                                        }
                                    }
                                }
                            }

                            if (!$existingCompetence) {
                                // Créer une nouvelle compétence
                                $competence = new Competence();
                                $competence->setNomFr($item['Competence_Nom_FR']);
                                $competence->setNomEn($item['Competence_Nom_EN']);

                                // Ajouter les catégories si elles existent
                                if (isset($item['Comp_Categorie_FR'])) {
                                    $competence->setCategorieFr($item['Comp_Categorie_FR']);
                                }
                                if (isset($item['Comp_Categorie_EN'])) {
                                    $competence->setCategorieEn($item['Comp_Categorie_EN']);
                                }

                                // Lier la compétence au quiz (cela définira aussi idmodule automatiquement)
                                try {
                                    $competence->setQuiz($quiz);
                                    // S'assurer que idmodule est correctement défini
                                    $competence->synchronizeIdmodule();
                                    // Définir explicitement idmodule pour être sûr
                                    if ($quiz->getIDModule() !== null) {
                                        $competence->setIdmodule($quiz->getIDModule());
                                    }
                                } catch (\Exception $e) {
                                    error_log("QuizController::createBatch - Exception lors de la définition du quiz pour la compétence: " . $e->getMessage());
                                }

                                // Ajouter la compétence au quiz si la méthode existe
                                if (method_exists($quiz, 'addCompetence')) {
                                    try {
                                        $quiz->addCompetence($competence);
                                    } catch (\Exception $e) {
                                        error_log("QuizController::createBatch - Exception lors de l'ajout de la compétence au quiz: " . $e->getMessage());
                                    }
                                }

                                // Persister la compétence explicitement
                                $this->em->persist($competence);

                                // Traitement des sous-compétences
                                if (isset($item['SousCompetence_Nom_FR']) && isset($item['SousCompetence_Nom_EN'])) {
                                    $sousCompetence = new SousCompetence();
                                    $sousCompetence->setNomFr($item['SousCompetence_Nom_FR']);
                                    $sousCompetence->setNomEn($item['SousCompetence_Nom_EN']);
                                    $sousCompetence->setCompetence($competence);
                                    $this->em->persist($sousCompetence);
                                }
                            } else {
                                // Utiliser la compétence existante et ajouter une sous-compétence si nécessaire
                                if (isset($item['SousCompetence_Nom_FR']) && isset($item['SousCompetence_Nom_EN'])) {
                                    // Vérifier si cette sous-compétence existe déjà
                                    $existingSousComp = null;
                                    foreach ($existingCompetence->getSousCompetences() as $sousComp) {
                                        if ($sousComp->getNomFr() === $item['SousCompetence_Nom_FR'] &&
                                            $sousComp->getNomEn() === $item['SousCompetence_Nom_EN']) {
                                            $existingSousComp = $sousComp;
                                            break;
                                        }
                                    }

                                    if (!$existingSousComp) {
                                        $sousCompetence = new SousCompetence();
                                        $sousCompetence->setNomFr($item['SousCompetence_Nom_FR']);
                                        $sousCompetence->setNomEn($item['SousCompetence_Nom_EN']);
                                        $sousCompetence->setCompetence($existingCompetence);
                                        $this->em->persist($sousCompetence);
                                    }
                                }
                            }
                        }

                        // Traitement des actions
                        if (isset($item['Action_Nom_FR']) && isset($item['Action_Nom_EN'])) {
                            // Vérifier si cette action existe déjà pour ce quiz
                            $existingAction = null;

                            // Vérifier si la méthode getActions existe et si la collection n'est pas null
                            if (method_exists($quiz, 'getActions')) {
                                $actions = $quiz->getActions();
                                if ($actions !== null) {
                                    foreach ($actions as $act) {
                                        if ($act->getNomFr() === $item['Action_Nom_FR'] &&
                                            $act->getNomEn() === $item['Action_Nom_EN']) {
                                            $existingAction = $act;
                                            break;
                                        }
                                    }
                                }
                            }

                            if (!$existingAction) {
                                $action = new Action();
                                $action->setNomFr($item['Action_Nom_FR']);
                                $action->setNomEn($item['Action_Nom_EN']);

                                // Ajouter les catégories si elles existent
                                if (isset($item['Action_Categorie_FR'])) {
                                    $action->setCategorieFr($item['Action_Categorie_FR']);
                                }
                                if (isset($item['Action_Categorie_EN'])) {
                                    $action->setCategorieEn($item['Action_Categorie_EN']);
                                }

                                // Lier l'action au quiz (cela définira aussi idmodule automatiquement)
                                try {
                                    $action->setQuiz($quiz);
                                    // S'assurer que idmodule est correctement défini
                                    $action->synchronizeIdmodule();
                                    // Définir explicitement idmodule pour être sûr
                                    if ($quiz->getIDModule() !== null) {
                                        $action->setIdmodule($quiz->getIDModule());
                                    }
                                } catch (\Exception $e) {
                                    error_log("QuizController::createBatch - Exception lors de la définition du quiz pour l'action: " . $e->getMessage());
                                }

                                // Ajouter l'action au quiz si la méthode existe
                                if (method_exists($quiz, 'addAction')) {
                                    try {
                                        $quiz->addAction($action);
                                    } catch (\Exception $e) {
                                        error_log("QuizController::createBatch - Exception lors de l'ajout de l'action au quiz: " . $e->getMessage());
                                    }
                                }

                                // Persister l'action explicitement
                                $this->em->persist($action);
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
                    $successCount++;

                    // Ajouter un résultat pour chaque élément du groupe
                    $results[] = $result;

                    // Ajouter des résultats de succès pour les autres éléments du groupe
                    for ($i = 1; $i < count($items); $i++) {
                        $otherResult = [
                            'index' => $items[$i]['original_index'],
                            'success' => true,
                            'message' => 'Traité dans le cadre du groupe IDModule: ' . $idModule
                        ];
                        $results[] = $otherResult;
                        $successCount++;
                    }
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

                    // Ajouter un résultat d'échec pour chaque élément du groupe
                    $results[] = $result;
                    for ($i = 1; $i < count($items); $i++) {
                        $otherResult = [
                            'index' => $items[$i]['original_index'],
                            'success' => false,
                            'error' => 'Échec du traitement du groupe IDModule: ' . $idModule . ' - ' . $e->getMessage()
                        ];
                        $results[] = $otherResult;
                        $errorCount++;
                    }
                }
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
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();

            error_log("QuizController::createBatch - Exception: " . $e->getMessage());
            error_log("QuizController::createBatch - File: " . $errorFile . " Line: " . $errorLine);
            error_log("QuizController::createBatch - Stack trace: " . $e->getTraceAsString());

            // Check if it's an "Undefined array key" warning
            if (strpos($e->getMessage(), 'Undefined array key') !== false) {
                error_log("QuizController::createBatch - This is an 'Undefined array key' error. Checking context...");

                // Try to extract the key name from the error message
                preg_match('/Undefined array key "(.*?)"/', $e->getMessage(), $matches);
                if (isset($matches[1])) {
                    $missingKey = $matches[1];
                    error_log("QuizController::createBatch - Missing key is: " . $missingKey);

                    // Check if data was received and log the first item for debugging
                    if (!empty($data) && is_array($data) && count($data) > 0) {
                        $firstItem = $data[0];
                        error_log("QuizController::createBatch - First item keys: " . implode(', ', array_keys($firstItem)));

                        // Check if a similar key exists (case-insensitive)
                        $foundSimilarKey = false;
                        foreach (array_keys($firstItem) as $key) {
                            if (strtolower($key) === strtolower($missingKey)) {
                                error_log("QuizController::createBatch - Found similar key: $key instead of $missingKey");
                                $foundSimilarKey = true;

                                // If the missing key is IDModule, try to use the similar key
                                if (strtolower($missingKey) === 'idmodule') {
                                    error_log("QuizController::createBatch - Attempting to recover by using the similar key: $key");
                                    // Reprocess the data with the correct key
                                    foreach ($data as &$item) {
                                        if (isset($item[$key])) {
                                            $item['IDModule'] = $item[$key];
                                            error_log("QuizController::createBatch - Added IDModule from $key: " . $item['IDModule']);
                                        }
                                    }

                                    // Try to process the data again
                                    return $this->createBatch(new Request([], [], [], [], [], [], json_encode($data)));
                                }
                            }
                        }

                        if (!$foundSimilarKey && strtolower($missingKey) === 'idmodule') {
                            error_log("QuizController::createBatch - No similar key found for IDModule. Attempting to add default values.");
                            // Add default IDModule to all items
                            foreach ($data as &$item) {
                                $item['IDModule'] = 'default_' . uniqid();
                                error_log("QuizController::createBatch - Added default IDModule: " . $item['IDModule']);
                            }

                            // Try to process the data again
                            return $this->createBatch(new Request([], [], [], [], [], [], json_encode($data)));
                        }
                    }
                }
            }

            return $this->json([
                'status' => 'error',
                'message' => 'Traitement par lot échoué',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $errorFile,
                'line' => $errorLine,
                'details' => 'Vérifiez que tous les champs requis sont présents et correctement nommés (IDModule, cours, Nom_FR, Nom_EN)'
            ], 500);
        }
    }

    #[Route('', name: 'api_quiz_list', methods: ['GET'])]
    public function list(Request $request, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $courseId = $request->query->get('cours');
            $IDModule = $request->query->get('IDModule');

            error_log("QuizController::list - Request received with parameters: " .
                      "cours=" . ($courseId ?? 'null') . ", IDModule=" . ($IDModule ?? 'null'));

            if ($courseId) {
                $cours = $this->coursRepository->find($courseId);
                if (!$cours) {
                    error_log("QuizController::list - Course not found with ID: " . $courseId);
                    return $this->json(['error' => 'Course not found'], 404);
                }

                error_log("QuizController::list - Course found: " . $cours->getTitre());
                $quizzes = $quizRepository->findBy(['cours' => $cours]);
                error_log("QuizController::list - Found " . count($quizzes) . " quizzes for course ID: " . $courseId);

                // Log the first quiz for debugging
                if (count($quizzes) > 0) {
                    $firstQuiz = $quizzes[0];
                    error_log("QuizController::list - First quiz: ID=" . $firstQuiz->getId() .
                              ", IDModule=" . $firstQuiz->getIDModule() .
                              ", Nom_FR=" . $firstQuiz->getNomFR());
                }
            } elseif ($IDModule) {
                error_log("QuizController::list - Searching by IDModule: " . $IDModule);
                $quizzes = $quizRepository->findBy(['IDModule' => $IDModule]);
                error_log("QuizController::list - Found " . count($quizzes) . " quizzes with IDModule: " . $IDModule);
            } else {
                error_log("QuizController::list - No specific parameters, returning all quizzes");
                $quizzes = $quizRepository->findAll();
                error_log("QuizController::list - Found " . count($quizzes) . " quizzes in total");
            }

            // Ensure we're returning a properly formatted response
            $response = $this->json($quizzes, 200, [], ['groups' => 'quiz:read']);
            error_log("QuizController::list - Response prepared with status 200");
            return $response;
        } catch (\Exception $e) {
            error_log("QuizController::list - Exception: " . $e->getMessage());
            error_log("QuizController::list - Stack trace: " . $e->getTraceAsString());
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

            return $this->json($quiz, 200, [], ['groups' => 'quiz:read']);
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

                // Toujours utiliser 'Evaluation' comme type, quelle que soit la valeur reçue
                $quiz->setType('Evaluation');

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
    public function deleteCompetence(string $IDModule, string $competenceId, CompetenceRepository $competenceRepository): JsonResponse
    {
        try {
            // Trouver la compétence par son ID
            $competence = $competenceRepository->find($competenceId);

            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée avec ID: ' . $competenceId], 404);
            }

            // Vérifier que la compétence appartient bien au module spécifié
            if ($competence->getIdmodule() !== $IDModule) {
                return $this->json(['message' => 'Cette compétence n\'appartient pas au module spécifié'], 400);
            }

            // Supprimer la compétence (et ses sous-compétences grâce à cascade)
            $this->em->remove($competence);
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
        QuizRepository $quizRepository,
        CompetenceRepository $competenceRepository,
        SousCompetenceRepository $sousCompetenceRepository
    ): JsonResponse
    {
        try {
            // Décoder les noms de sous-compétence
            $decodedNomFR = urldecode($sousCompetenceNomFR);
            $decodedNomEN = urldecode($sousCompetenceNomEN);

            // Trouver le quiz par son IDModule
            $quiz = $quizRepository->findOneBy(['IDModule' => $IDModule]);

            if (!$quiz) {
                return $this->json(['message' => 'Quiz non trouvé avec IDModule: ' . $IDModule], 404);
            }

            // Trouver la compétence par son ID
            $competence = $competenceRepository->find($competenceId);

            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée avec ID: ' . $competenceId], 404);
            }

            // Vérifier que la compétence appartient bien au module spécifié
            if ($competence->getIdmodule() !== $IDModule) {
                return $this->json(['message' => 'Cette compétence n\'appartient pas au module spécifié'], 400);
            }

            // Trouver la sous-compétence par son nom et sa compétence parente
            $sousCompetence = $sousCompetenceRepository->findOneBy([
                'competence' => $competence,
                'nom_fr' => $decodedNomFR,
                'nom_en' => $decodedNomEN
            ]);

            if (!$sousCompetence) {
                return $this->json(['message' => 'Sous-compétence non trouvée'], 404);
            }

            // Supprimer la sous-compétence
            $this->em->remove($sousCompetence);
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
        CompetenceRepository $competenceRepository,
        ActionRepository $actionRepository
    ): JsonResponse
    {
        try {
            // Si competenceId est fourni, on vérifie que la compétence existe et appartient au module
            if ($competenceId !== '0') {
                // Trouver la compétence par son ID
                $competence = $competenceRepository->find($competenceId);

                if (!$competence) {
                    return $this->json(['message' => 'Compétence non trouvée avec ID: ' . $competenceId], 404);
                }

                // Vérifier que la compétence appartient bien au module spécifié
                if ($competence->getIdmodule() !== $IDModule) {
                    return $this->json(['message' => 'Cette compétence n\'appartient pas au module spécifié'], 400);
                }
            }

            // Trouver l'action par son nom et son idmodule
            $action = $actionRepository->findOneBy([
                'idmodule' => $IDModule,
                'nom_fr' => $actionNomFR,
                'nom_en' => $actionNomEN
            ]);

            if (!$action) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            // Supprimer l'action
            $this->em->remove($action);
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
        CompetenceRepository $competenceRepository
    ): JsonResponse
    {
        try {
            // Trouver la compétence par son ID
            $competence = $competenceRepository->find($competenceId);

            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée avec ID: ' . $competenceId], 404);
            }

            // Vérifier que la compétence appartient bien au module spécifié
            if ($competence->getIdmodule() !== $IDModule) {
                return $this->json(['message' => 'Cette compétence n\'appartient pas au module spécifié'], 400);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour la compétence
            if (isset($data['nom_fr']) || isset($data['Competence_Nom_FR'])) {
                $competence->setNomFr(isset($data['nom_fr']) ? $data['nom_fr'] : $data['Competence_Nom_FR']);
            }

            if (isset($data['nom_en']) || isset($data['Competence_Nom_EN'])) {
                $competence->setNomEn(isset($data['nom_en']) ? $data['nom_en'] : $data['Competence_Nom_EN']);
            }

            if (isset($data['categorie_fr']) || isset($data['Comp_Categorie_FR'])) {
                $competence->setCategorieFr(isset($data['categorie_fr']) ? $data['categorie_fr'] : $data['Comp_Categorie_FR']);
            }

            if (isset($data['categorie_en']) || isset($data['Comp_Categorie_EN'])) {
                $competence->setCategorieEn(isset($data['categorie_en']) ? $data['categorie_en'] : $data['Comp_Categorie_EN']);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($competence);
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

            // Persister les modifications
            $this->em->persist($competence);
            $this->em->flush();

            // Vérifier que les modifications ont bien été enregistrées
            $updatedCompetence = $competenceRepository->find($competenceId);

            return $this->json([
                'message' => 'Compétence mise à jour avec succès',
                'competence' => $updatedCompetence
            ], 200, [], ['groups' => 'competence:read']);
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
        QuizRepository $quizRepository,
        CompetenceRepository $competenceRepository,
        SousCompetenceRepository $sousCompetenceRepository
    ): JsonResponse
    {
        try {
            // Décoder les noms de sous-compétence
            $decodedNomFR = urldecode($sousCompetenceNomFR);
            $decodedNomEN = urldecode($sousCompetenceNomEN);

            // Récupérer les données de la requête
            $data = json_decode($request->getContent(), true);

            // Trouver le quiz par son IDModule
            $quiz = $quizRepository->findOneBy(['IDModule' => $IDModule]);

            if (!$quiz) {
                return $this->json(['message' => 'Quiz non trouvé avec IDModule: ' . $IDModule], 404);
            }

            // Trouver la compétence par son ID
            $competence = $competenceRepository->find($competenceId);

            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée avec ID: ' . $competenceId], 404);
            }

            // Vérifier que la compétence appartient bien au module spécifié
            if ($competence->getIdmodule() !== $IDModule) {
                return $this->json(['message' => 'Cette compétence n\'appartient pas au module spécifié'], 400);
            }

            // Trouver la sous-compétence par son nom et sa compétence parente
            $sousCompetence = $sousCompetenceRepository->findOneBy([
                'competence' => $competence,
                'nom_fr' => $decodedNomFR,
                'nom_en' => $decodedNomEN
            ]);

            if (!$sousCompetence) {
                return $this->json(['message' => 'Sous-compétence non trouvée'], 404);
            }

            // Mettre à jour la sous-compétence
            if (isset($data['nom_fr']) || isset($data['SousCompetence_Nom_FR'])) {
                $sousCompetence->setNomFr(isset($data['nom_fr']) ? $data['nom_fr'] : $data['SousCompetence_Nom_FR']);
            }

            if (isset($data['nom_en']) || isset($data['SousCompetence_Nom_EN'])) {
                $sousCompetence->setNomEn(isset($data['nom_en']) ? $data['nom_en'] : $data['SousCompetence_Nom_EN']);
            }

            // Persister et enregistrer les modifications
            $this->em->persist($sousCompetence);
            $this->em->flush();

            return $this->json([
                'message' => 'Sous-compétence mise à jour avec succès',
                'sous_competence' => $sousCompetence
            ], 200, [], ['groups' => 'sous_competence:read']);
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
        QuizRepository $quizRepository,
        CompetenceRepository $competenceRepository,
        SousCompetenceRepository $sousCompetenceRepository
    ): JsonResponse
    {
        try {
            // Trouver le quiz par son IDModule
            $quiz = $quizRepository->findOneBy(['IDModule' => $IDModule]);

            if (!$quiz) {
                return $this->json(['message' => 'Quiz non trouvé avec IDModule: ' . $IDModule], 404);
            }

            // Trouver la compétence par son ID
            $competence = $competenceRepository->find($competenceId);

            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée avec ID: ' . $competenceId], 404);
            }

            // Vérifier que la compétence appartient bien au module spécifié
            if ($competence->getIdmodule() !== $IDModule) {
                return $this->json(['message' => 'Cette compétence n\'appartient pas au module spécifié'], 400);
            }

            // Trouver la sous-compétence par son ID
            $sousCompetence = $sousCompetenceRepository->find($id);

            if (!$sousCompetence) {
                return $this->json(['message' => 'Sous-compétence non trouvée avec ID: ' . $id], 404);
            }

            // Vérifier que la sous-compétence appartient bien à la compétence
            if ($sousCompetence->getCompetence()->getId() !== $competence->getId()) {
                return $this->json(['message' => 'Cette sous-compétence n\'appartient pas à la compétence spécifiée'], 400);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour la sous-compétence
            if (isset($data['nom_fr']) || isset($data['SousCompetence_Nom_FR'])) {
                $sousCompetence->setNomFr(isset($data['nom_fr']) ? $data['nom_fr'] : $data['SousCompetence_Nom_FR']);
            }

            if (isset($data['nom_en']) || isset($data['SousCompetence_Nom_EN'])) {
                $sousCompetence->setNomEn(isset($data['nom_en']) ? $data['nom_en'] : $data['SousCompetence_Nom_EN']);
            }

            // Persister et enregistrer les modifications
            $this->em->persist($sousCompetence);
            $this->em->flush();

            return $this->json([
                'message' => 'Sous-compétence mise à jour avec succès',
                'sous_competence' => $sousCompetence
            ], 200, [], ['groups' => 'sous_competence:read']);
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
        ActionRepository $actionRepository
    ): JsonResponse
    {
        try {
            // Décoder les noms d'action
            $decodedNomFR = urldecode($actionNomFR);
            $decodedNomEN = urldecode($actionNomEN);

            // Récupérer les données de la requête
            $data = json_decode($request->getContent(), true);

            // Trouver l'action par son nom et son idmodule
            $action = $actionRepository->findOneBy([
                'idmodule' => $IDModule,
                'nom_fr' => $decodedNomFR,
                'nom_en' => $decodedNomEN
            ]);

            if (!$action) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            // Mettre à jour l'action
            if (isset($data['nom_fr']) || isset($data['Action_Nom_FR'])) {
                $action->setNomFr(isset($data['nom_fr']) ? $data['nom_fr'] : $data['Action_Nom_FR']);
            }

            if (isset($data['nom_en']) || isset($data['Action_Nom_EN'])) {
                $action->setNomEn(isset($data['nom_en']) ? $data['nom_en'] : $data['Action_Nom_EN']);
            }

            if (isset($data['categorie_fr']) || isset($data['Action_Categorie_FR'])) {
                $action->setCategorieFr(isset($data['categorie_fr']) ? $data['categorie_fr'] : $data['Action_Categorie_FR']);
            }

            if (isset($data['categorie_en']) || isset($data['Action_Categorie_EN'])) {
                $action->setCategorieEn(isset($data['categorie_en']) ? $data['categorie_en'] : $data['Action_Categorie_EN']);
            }

            // Persister et enregistrer les modifications
            $this->em->persist($action);
            $this->em->flush();

            return $this->json([
                'message' => 'Action mise à jour avec succès',
                'action' => $action
            ], 200, [], ['groups' => 'action:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/competence/create', name: 'api_competence_create', methods: ['POST'])]
    public function createCompetence(Request $request, CompetenceRepository $competenceRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);


            // Validation des champs requis
            $requiredFields = ['IDModule', 'nom_fr', 'nom_en'];
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

            // Vérifier si une compétence avec le même nom existe déjà pour cet idmodule
            $existingCompetence = $competenceRepository->findOneBy([
                'idmodule' => $data['IDModule'],
                'nom_fr' => $data['nom_fr'],
                'nom_en' => $data['nom_en']
            ]);

            if ($existingCompetence) {
                return $this->json([
                    'error' => 'Une compétence avec ce nom existe déjà pour ce module',
                    'id_module' => $data['IDModule']
                ], 409); // Conflict
            }

            // Trouver le quiz par IDModule
            $quiz = $this->em->getRepository(Quiz::class)->findOneBy(['IDModule' => $data['IDModule']]);
            if (!$quiz) {
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }

            // Créer la compétence
            $competence = new Competence();

            // Lier la compétence au quiz (cela définira aussi idmodule automatiquement)
            $competence->setQuiz($quiz);

            $competence->setNomFr($data['nom_fr']);
            $competence->setNomEn($data['nom_en']);

            if (isset($data['categorie_fr'])) {
                $competence->setCategorieFr($data['categorie_fr']);
            }

            if (isset($data['categorie_en'])) {
                $competence->setCategorieEn($data['categorie_en']);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($competence);
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
                $this->em->persist($competence);
                $this->em->flush();
            } catch (\Exception $e) {
                // Vérifier si c'est une erreur de duplication
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    return $this->json([
                        'error' => 'Cette compétence existe déjà (erreur de duplication)',
                        'message' => $e->getMessage()
                    ], 409);
                }

                return $this->json([
                    'error' => 'Erreur lors de la persistance de la compétence',
                    'message' => $e->getMessage()
                ], 500);
            }

            // Retourner la réponse
            return $this->json([
                'message' => 'Compétence créée avec succès',
                'competence' => $competence
            ], 201, [], ['groups' => 'competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/sous-competence/create', name: 'api_sous_competence_create', methods: ['POST'])]
    public function createSousCompetence(Request $request, CompetenceRepository $competenceRepository, SousCompetenceRepository $sousCompetenceRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Log des données reçues
            error_log('Données reçues pour createSousCompetence: ' . print_r($data, true));

            // Validation des champs requis
            $requiredFields = ['IDModule', 'Competence_ID', 'SousCompetence_Nom_FR', 'SousCompetence_Nom_EN'];
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

            // Trouver le quiz par IDModule
            $quiz = $this->em->getRepository(Quiz::class)->findOneBy(['IDModule' => $data['IDModule']]);
            if (!$quiz) {
                error_log('Aucun quiz trouvé avec IDModule: ' . $data['IDModule']);
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }

            // Trouver la compétence par son ID
            $competence = $competenceRepository->find($data['Competence_ID']);
            if (!$competence) {
                error_log('Aucune compétence trouvée avec ID: ' . $data['Competence_ID']);
                return $this->json([
                    'error' => 'Aucune compétence trouvée avec cet ID'
                ], 404);
            }

            // Vérifier si la sous-compétence existe déjà pour cette compétence
            $existingSousCompetence = $sousCompetenceRepository->findOneBy([
                'competence' => $competence,
                'nom_fr' => $data['SousCompetence_Nom_FR'],
                'nom_en' => $data['SousCompetence_Nom_EN']
            ]);

            if ($existingSousCompetence) {
                error_log('Sous-compétence existante: ' . $data['SousCompetence_Nom_FR']);
                return $this->json([
                    'error' => 'Cette sous-compétence existe déjà pour cette compétence'
                ], 409); // Conflict
            }

            try {
                // Créer une nouvelle sous-compétence
                $sousCompetence = new SousCompetence();
                $sousCompetence->setCompetence($competence);
                $sousCompetence->setNomFr($data['SousCompetence_Nom_FR']);
                $sousCompetence->setNomEn($data['SousCompetence_Nom_EN']);

                error_log('Sous-compétence créée avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la création de la sous-compétence: ' . $e->getMessage());
                throw $e;
            }

            // Validation de l'entité
            $errors = $this->validator->validate($sousCompetence);
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
                $this->em->persist($sousCompetence);
                $this->em->flush();
                error_log('Sous-compétence persistée avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance de la sous-compétence: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
                return $this->json([
                    'error' => 'Erreur lors de la persistance de la sous-compétence',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return $this->json([
                'message' => 'Sous-compétence créée avec succès',
                'sous_competence' => $sousCompetence
            ], 201, [], ['groups' => 'sous_competence:read']);
        } catch (\Exception $e) {
            error_log('Exception dans createSousCompetence: ' . $e->getMessage());
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/action/create', name: 'api_action_create', methods: ['POST'])]
    public function createAction(Request $request, QuizRepository $quizRepository, ActionRepository $actionRepository): JsonResponse
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

            // Trouver le quiz par IDModule
            $quiz = $this->em->getRepository(Quiz::class)->findOneBy(['IDModule' => $data['IDModule']]);
            if (!$quiz) {
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }

            // Vérifier si l'action existe déjà pour cet idmodule
            $existingAction = $actionRepository->findOneBy([
                'idmodule' => $data['IDModule'],
                'nom_fr' => $data['Action_Nom_FR'],
                'nom_en' => $data['Action_Nom_EN']
            ]);

            if ($existingAction) {
                return $this->json([
                    'error' => 'Cette action existe déjà pour ce quiz'
                ], 409); // Conflict
            }

            // Créer une nouvelle action
            $action = new Action();

            // Lier l'action au quiz (cela définira aussi idmodule automatiquement)
            $action->setQuiz($quiz);

            $action->setNomFr($data['Action_Nom_FR']);
            $action->setNomEn($data['Action_Nom_EN']);

            if (isset($data['Action_Categorie_FR'])) {
                $action->setCategorieFr($data['Action_Categorie_FR']);
            }

            if (isset($data['Action_Categorie_EN'])) {
                $action->setCategorieEn($data['Action_Categorie_EN']);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($action);
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
                $this->em->persist($action);
                $this->em->flush();
                error_log('Action persistée avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance de l\'action: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
                return $this->json([
                    'error' => 'Erreur lors de la persistance de l\'action',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return $this->json([
                'message' => 'Action créée avec succès',
                'action' => $action
            ], 201, [], ['groups' => 'action:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/quiz-action/create', name: 'api_quiz_action_create', methods: ['POST'])]
    public function createQuizAction(Request $request, QuizRepository $quizRepository, ActionRepository $actionRepository): JsonResponse
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

            // Trouver le quiz par IDModule
            $quiz = $this->em->getRepository(Quiz::class)->findOneBy(['IDModule' => $data['IDModule']]);
            if (!$quiz) {
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule'
                ], 404);
            }

            // Vérifier si l'action existe déjà pour cet idmodule
            $existingAction = $actionRepository->findOneBy([
                'idmodule' => $data['IDModule'],
                'nom_fr' => $data['Action_Nom_FR'],
                'nom_en' => $data['Action_Nom_EN']
            ]);

            if ($existingAction) {
                error_log('Action existante trouvée avec ID: ' . $existingAction->getId());
                return $this->json([
                    'error' => 'Cette action existe déjà pour ce quiz',
                    'existing_id' => $existingAction->getId()
                ], 409); // Conflict
            }

            error_log('Aucune action existante trouvée, création d\'une nouvelle action');

            // Créer une nouvelle action
            $action = new Action();

            // Lier l'action au quiz (cela définira aussi idmodule automatiquement)
            $action->setQuiz($quiz);

            $action->setNomFr($data['Action_Nom_FR']);
            $action->setNomEn($data['Action_Nom_EN']);

            if (isset($data['Action_Categorie_FR'])) {
                $action->setCategorieFr($data['Action_Categorie_FR']);
            }

            if (isset($data['Action_Categorie_EN'])) {
                $action->setCategorieEn($data['Action_Categorie_EN']);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($action);
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
                $this->em->persist($action);
                $this->em->flush();
                error_log('Action persistée avec succès');
            } catch (\Exception $e) {
                error_log('Erreur lors de la persistance de l\'action: ' . $e->getMessage());
                error_log('Trace: ' . $e->getTraceAsString());
                return $this->json([
                    'error' => 'Erreur lors de la persistance de l\'action',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }

            return $this->json([
                'message' => 'Action au niveau du quiz créée avec succès',
                'action' => $action
            ], 201, [], ['groups' => 'action:read']);
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
        ActionRepository $actionRepository
    ): JsonResponse
    {
        try {
            // Trouver l'action par son ID
            $action = $actionRepository->find($id);

            if (!$action) {
                return $this->json(['message' => 'Action non trouvée avec ID ' . $id], 404);
            }

            // Supprimer l'action
            $this->em->remove($action);
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
        QuizRepository $quizRepository,
        ActionRepository $actionRepository
    ): JsonResponse
    {
        try {
            // Trouver le quiz par son IDModule
            $quiz = $quizRepository->findOneBy(['IDModule' => $IDModule]);

            if (!$quiz) {
                return $this->json(['message' => 'Quiz non trouvé avec IDModule: ' . $IDModule], 404);
            }

            // Trouver l'action par son nom et son idmodule
            $action = $actionRepository->findOneBy([
                'idmodule' => $IDModule,
                'nom_fr' => $actionNomFR,
                'nom_en' => $actionNomEN
            ]);

            if (!$action) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            // Supprimer l'action
            $this->em->remove($action);
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
        ActionRepository $actionRepository
    ): JsonResponse
    {
        try {
            // Trouver l'action par son ID
            $action = $actionRepository->find($id);

            if (!$action) {
                return $this->json(['message' => 'Action non trouvée avec ID ' . $id], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour l'action
            if (isset($data['nom_fr']) || isset($data['Action_Nom_FR'])) {
                $action->setNomFr(isset($data['nom_fr']) ? $data['nom_fr'] : $data['Action_Nom_FR']);
            }

            if (isset($data['nom_en']) || isset($data['Action_Nom_EN'])) {
                $action->setNomEn(isset($data['nom_en']) ? $data['nom_en'] : $data['Action_Nom_EN']);
            }

            if (isset($data['categorie_fr']) || isset($data['Action_Categorie_FR'])) {
                $action->setCategorieFr(isset($data['categorie_fr']) ? $data['categorie_fr'] : $data['Action_Categorie_FR']);
            }

            if (isset($data['categorie_en']) || isset($data['Action_Categorie_EN'])) {
                $action->setCategorieEn(isset($data['categorie_en']) ? $data['categorie_en'] : $data['Action_Categorie_EN']);
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
        QuizRepository $quizRepository,
        ActionRepository $actionRepository
    ): JsonResponse
    {
        try {
            // Trouver le quiz par son IDModule
            $quiz = $quizRepository->findOneBy(['IDModule' => $IDModule]);

            if (!$quiz) {
                return $this->json(['message' => 'Quiz non trouvé avec IDModule: ' . $IDModule], 404);
            }

            // Trouver l'action par son nom et son idmodule
            $action = $actionRepository->findOneBy([
                'idmodule' => $IDModule,
                'nom_fr' => $actionNomFR,
                'nom_en' => $actionNomEN
            ]);

            if (!$action) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Mettre à jour l'action
            if (isset($data['nom_fr']) || isset($data['Action_Nom_FR'])) {
                $action->setNomFr(isset($data['nom_fr']) ? $data['nom_fr'] : $data['Action_Nom_FR']);
            }

            if (isset($data['nom_en']) || isset($data['Action_Nom_EN'])) {
                $action->setNomEn(isset($data['nom_en']) ? $data['nom_en'] : $data['Action_Nom_EN']);
            }

            if (isset($data['categorie_fr']) || isset($data['Action_Categorie_FR'])) {
                $action->setCategorieFr(isset($data['categorie_fr']) ? $data['categorie_fr'] : $data['Action_Categorie_FR']);
            }

            if (isset($data['categorie_en']) || isset($data['Action_Categorie_EN'])) {
                $action->setCategorieEn(isset($data['categorie_en']) ? $data['categorie_en'] : $data['Action_Categorie_EN']);
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