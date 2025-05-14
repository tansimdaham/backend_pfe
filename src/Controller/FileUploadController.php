<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api')]
class FileUploadController extends AbstractController
{
    #[Route('/upload/profile-image', name: 'api_upload_profile_image', methods: ['POST'])]
    public function uploadProfileImage(Request $request): JsonResponse
    {
        try {
            // Récupérer les données de l'image en base64
            $data = json_decode($request->getContent(), true);
            
            if (!isset($data['image']) || empty($data['image'])) {
                return $this->json(['message' => 'Aucune image fournie'], Response::HTTP_BAD_REQUEST);
            }
            
            // Extraire les données de l'image base64
            $imageData = $data['image'];
            
            // Vérifier si l'image est au format base64
            if (strpos($imageData, 'data:image') !== 0) {
                return $this->json(['message' => 'Format d\'image invalide'], Response::HTTP_BAD_REQUEST);
            }
            
            // Extraire le type MIME et les données binaires
            list($type, $imageData) = explode(';', $imageData);
            list(, $imageData) = explode(',', $imageData);
            $imageData = base64_decode($imageData);
            
            // Générer un nom de fichier unique
            $filename = uniqid() . '.png';
            
            // Définir le chemin de sauvegarde
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profile-images/';
            
            // Créer le répertoire s'il n'existe pas
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Sauvegarder l'image
            file_put_contents($uploadDir . $filename, $imageData);
            
            // Retourner le chemin relatif de l'image
            return $this->json([
                'success' => true,
                'path' => '/uploads/profile-images/' . $filename
            ], Response::HTTP_OK);
            
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement de l\'image: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
