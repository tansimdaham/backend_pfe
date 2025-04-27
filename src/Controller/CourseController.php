<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\CoursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cours')]
class CourseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CoursRepository $coursRepository
    ) {}

    #[Route('', name: 'api_cours_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $cours = $this->coursRepository->findAll();
        return $this->json($cours, 200, [], ['groups' => 'cours:read']);
    }

    #[Route('', name: 'api_cours_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $cours = new Cours();
        $cours->setTitre($data['titre'] ?? '');
        $cours->setDescription($data['description'] ?? '');

        $this->em->persist($cours);
        $this->em->flush();

        return $this->json($cours, 201, [], ['groups' => 'cours:read']);
    }

    #[Route('/{id}', name: 'api_cours_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $cours = $this->coursRepository->find($id);
        if (!$cours) {
            return $this->json(['error' => 'Cours non trouvé'], 404);
        }
        return $this->json($cours, 200, [], ['groups' => 'cours:read']);
    }

    #[Route('/{id}', name: 'api_cours_update', methods: ['PUT'])]
    public function update(Request $request, int $id): JsonResponse
    {
        $cours = $this->coursRepository->find($id);
        if (!$cours) {
            return $this->json(['error' => 'Cours non trouvé'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $cours->setTitre($data['titre'] ?? $cours->getTitre());
        $cours->setDescription($data['description'] ?? $cours->getDescription());

        $this->em->flush();

        return $this->json($cours, 200, [], ['groups' => 'cours:read']);
    }

    #[Route('/{id}', name: 'api_cours_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $cours = $this->coursRepository->find($id);
        if (!$cours) {
            return $this->json(['error' => 'Cours non trouvé'], 404);
        }

        $this->em->remove($cours);
        $this->em->flush();

        return $this->json(null, 204);
    }
}