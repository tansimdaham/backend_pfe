<?php

namespace App\Controller;

use App\Entity\Action;
use App\Entity\Competence;
use App\Entity\Quiz;
use App\Repository\ActionRepository;
use App\Repository\CompetenceRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/action')]
class ActionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_action_list', methods: ['GET'])]
    public function list(Request $request, ActionRepository $actionRepository): JsonResponse
    {
        try {
            $idModule = $request->query->get('idmodule');

            if ($idModule) {
                $actions = $actionRepository->findByIdModule($idModule);
            } else {
                $actions = $actionRepository->findAll();
            }

            return $this->json($actions, 200, [], ['groups' => 'action:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_action_show', methods: ['GET'])]
    public function show(int $id, ActionRepository $actionRepository): JsonResponse
    {
        try {
            $action = $actionRepository->find($id);
            if (!$action) {
                return $this->json(['message' => 'Action not found'], 404);
            }

            return $this->json($action, 200, [], ['groups' => 'action:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_action_create', methods: ['POST'])]
    public function create(Request $request, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des champs requis
            $requiredFields = ['nom_fr', 'nom_en', 'idmodule'];
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

            // Trouver le quiz par son IDModule
            $quiz = $quizRepository->findOneBy(['IDModule' => $data['idmodule']]);
            if (!$quiz) {
                return $this->json([
                    'error' => 'Aucun quiz trouvé avec cet IDModule',
                    'idmodule' => $data['idmodule']
                ], 404);
            }

            // Vérifier si une action avec le même nom existe déjà pour cet idmodule
            $existingAction = $this->em->getRepository(Action::class)->findOneBy([
                'idmodule' => $data['idmodule'],
                'nom_fr' => $data['nom_fr'],
                'nom_en' => $data['nom_en']
            ]);

            if ($existingAction) {
                return $this->json([
                    'error' => 'Une action avec ce nom existe déjà pour ce module'
                ], 409); // Conflict
            }

            // Créer l'action
            $action = new Action();
            $action->setQuiz($quiz); // Établir la relation avec le quiz (cela définira aussi idmodule)
            $action->setNomFr($data['nom_fr']);
            $action->setNomEn($data['nom_en']);

            if (isset($data['categorie_fr'])) {
                $action->setCategorieFr($data['categorie_fr']);
            }

            if (isset($data['categorie_en'])) {
                $action->setCategorieEn($data['categorie_en']);
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

            $this->em->persist($action);
            $this->em->flush();

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

    #[Route('/{id}', name: 'api_action_update', methods: ['PUT'])]
    public function update(Request $request, int $id, ActionRepository $actionRepository, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $action = $actionRepository->find($id);
            if (!$action) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Si idmodule est fourni et différent de l'actuel, mettre à jour la relation avec Quiz
            if (isset($data['idmodule']) && $data['idmodule'] !== $action->getIdmodule()) {
                $quiz = $quizRepository->findOneBy(['IDModule' => $data['idmodule']]);
                if (!$quiz) {
                    return $this->json([
                        'error' => 'Aucun quiz trouvé avec cet IDModule',
                        'idmodule' => $data['idmodule']
                    ], 404);
                }
                $action->setQuiz($quiz);
            }

            if (isset($data['nom_fr'])) {
                $action->setNomFr($data['nom_fr']);
            }
            if (isset($data['nom_en'])) {
                $action->setNomEn($data['nom_en']);
            }
            if (isset($data['categorie_fr'])) {
                $action->setCategorieFr($data['categorie_fr']);
            }
            if (isset($data['categorie_en'])) {
                $action->setCategorieEn($data['categorie_en']);
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

    #[Route('/{id}', name: 'api_action_delete', methods: ['DELETE'])]
    public function delete(int $id, ActionRepository $actionRepository): JsonResponse
    {
        try {
            $action = $actionRepository->find($id);
            if (!$action) {
                return $this->json(['message' => 'Action non trouvée'], 404);
            }

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
}
