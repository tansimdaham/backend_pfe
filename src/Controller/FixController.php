<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FixController extends AbstractController
{
    #[Route('/api/fix', name: 'api_fix')]
    public function fix(): JsonResponse
    {
        return $this->json(['message' => 'Configuration fixed']);
    }
}
