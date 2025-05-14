<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Entity\Quiz;
use App\Repository\CompetenceRepository;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/competence')]
class CompetenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}


    #[Route('', name: 'api_competence_list', methods: ['GET'])]
    public function list(Request $request, CompetenceRepository $competenceRepository): JsonResponse
    {
        try {
            $idModule = $request->query->get('idmodule');

            if ($idModule) {
                $competences = $competenceRepository->findByIdModule($idModule);
            } else {
                $competences = $competenceRepository->findAll();
            }

            return $this->json($competences, 200, [], ['groups' => 'competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_competence_show', methods: ['GET'])]
    public function show(int $id, CompetenceRepository $competenceRepository): JsonResponse
    {
        try {
            $competence = $competenceRepository->find($id);
            if (!$competence) {
                return $this->json(['message' => 'Competence not found'], 404);
            }

            return $this->json($competence, 200, [], ['groups' => 'competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_competence_create', methods: ['POST', 'OPTIONS'])]
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

            // Vérifier si une compétence avec le même nom existe déjà pour cet idmodule
            $existingCompetence = $this->em->getRepository(Competence::class)->findOneBy([
                'idmodule' => $data['idmodule'],
                'nom_fr' => $data['nom_fr'],
                'nom_en' => $data['nom_en']
            ]);

            if ($existingCompetence) {
                return $this->json([
                    'error' => 'Une compétence avec ce nom existe déjà pour ce module'
                ], 409); // Conflict
            }

            // Créer la compétence
            $competence = new Competence();
            $competence->setQuiz($quiz); // Établir la relation avec le quiz (cela définira aussi idmodule)
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

            $this->em->persist($competence);
            $this->em->flush();

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

    #[Route('/{id}', name: 'api_competence_update', methods: ['PUT'])]
    public function update(Request $request, int $id, CompetenceRepository $competenceRepository, QuizRepository $quizRepository): JsonResponse
    {
        try {
            $competence = $competenceRepository->find($id);
            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            // Si idmodule est fourni et différent de l'actuel, mettre à jour la relation avec Quiz
            if (isset($data['idmodule']) && $data['idmodule'] !== $competence->getIdmodule()) {
                $quiz = $quizRepository->findOneBy(['IDModule' => $data['idmodule']]);
                if (!$quiz) {
                    return $this->json([
                        'error' => 'Aucun quiz trouvé avec cet IDModule',
                        'idmodule' => $data['idmodule']
                    ], 404);
                }
                $competence->setQuiz($quiz);
            }

            if (isset($data['nom_fr'])) {
                $competence->setNomFr($data['nom_fr']);
            }
            if (isset($data['nom_en'])) {
                $competence->setNomEn($data['nom_en']);
            }
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

            $this->em->flush();

            return $this->json([
                'message' => 'Compétence mise à jour avec succès',
                'competence' => $competence
            ], 200, [], ['groups' => 'competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_competence_delete', methods: ['DELETE'])]
    public function delete(int $id, CompetenceRepository $competenceRepository): JsonResponse
    {
        try {
            $competence = $competenceRepository->find($id);
            if (!$competence) {
                return $this->json(['message' => 'Compétence non trouvée'], 404);
            }

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
}
