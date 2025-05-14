<?php

namespace App\Controller;

use App\Entity\Competence;
use App\Entity\Quiz;
use App\Entity\SousCompetence;
use App\Repository\CompetenceRepository;
use App\Repository\QuizRepository;
use App\Repository\SousCompetenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/sous-competence')]
class SousCompetenceController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_sous_competence_list', methods: ['GET'])]
    public function list(Request $request, SousCompetenceRepository $sousCompetenceRepository): JsonResponse
    {
        try {
            $competenceId = $request->query->get('competence');

            if ($competenceId) {
                $competence = $this->em->getRepository(Competence::class)->find($competenceId);
                if (!$competence) {
                    return $this->json(['error' => 'Competence not found'], 404);
                }
                $sousCompetences = $sousCompetenceRepository->findBy(['competence' => $competence]);
            } else {
                $sousCompetences = $sousCompetenceRepository->findAll();
            }

            return $this->json($sousCompetences, 200, [], ['groups' => 'sous_competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_sous_competence_show', methods: ['GET'])]
    public function show(int $id, SousCompetenceRepository $sousCompetenceRepository): JsonResponse
    {
        try {
            $sousCompetence = $sousCompetenceRepository->find($id);
            if (!$sousCompetence) {
                return $this->json(['message' => 'Sous-compétence not found'], 404);
            }

            return $this->json($sousCompetence, 200, [], ['groups' => 'sous_competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('', name: 'api_sous_competence_create', methods: ['POST'])]
    public function create(Request $request, CompetenceRepository $competenceRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            // Validation des champs requis
            $requiredFields = ['competence_id', 'nom_fr', 'nom_en'];
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

            // Récupérer la compétence
            $competence = $competenceRepository->find($data['competence_id']);
            if (!$competence) {
                return $this->json([
                    'error' => 'Compétence non trouvée'
                ], 404);
            }

            // Vérifier si une sous-compétence avec le même nom existe déjà pour cette compétence
            $existingSousCompetence = $this->em->getRepository(SousCompetence::class)->findOneBy([
                'competence' => $competence,
                'nom_fr' => $data['nom_fr'],
                'nom_en' => $data['nom_en']
            ]);

            if ($existingSousCompetence) {
                return $this->json([
                    'error' => 'Une sous-compétence avec ce nom existe déjà pour cette compétence'
                ], 409); // Conflict
            }

            // Créer la sous-compétence
            $sousCompetence = new SousCompetence();
            $sousCompetence->setCompetence($competence);
            $sousCompetence->setNomFr($data['nom_fr']);
            $sousCompetence->setNomEn($data['nom_en']);

            // Validation de l'entité
            $errors = $this->validator->validate($sousCompetence);
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

            $this->em->persist($sousCompetence);
            $this->em->flush();

            return $this->json([
                'message' => 'Sous-compétence créée avec succès',
                'sous_competence' => $sousCompetence
            ], 201, [], ['groups' => 'sous_competence:read']);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur serveur',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}', name: 'api_sous_competence_update', methods: ['PUT'])]
    public function update(Request $request, int $id, SousCompetenceRepository $sousCompetenceRepository): JsonResponse
    {
        try {
            $sousCompetence = $sousCompetenceRepository->find($id);
            if (!$sousCompetence) {
                return $this->json(['message' => 'Sous-compétence non trouvée'], 404);
            }

            $data = json_decode($request->getContent(), true);

            if (isset($data['nom_fr'])) {
                $sousCompetence->setNomFr($data['nom_fr']);
            }
            if (isset($data['nom_en'])) {
                $sousCompetence->setNomEn($data['nom_en']);
            }

            // Validation de l'entité
            $errors = $this->validator->validate($sousCompetence);
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

    #[Route('/{id}', name: 'api_sous_competence_delete', methods: ['DELETE'])]
    public function delete(int $id, SousCompetenceRepository $sousCompetenceRepository): JsonResponse
    {
        try {
            $sousCompetence = $sousCompetenceRepository->find($id);
            if (!$sousCompetence) {
                return $this->json(['message' => 'Sous-compétence non trouvée'], 404);
            }

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
}
