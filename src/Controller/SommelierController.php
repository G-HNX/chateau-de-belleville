<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SommelierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class SommelierController extends AbstractController
{
    #[Route('/api/sommelier', name: 'app_sommelier_chat', methods: ['POST'])]
    public function chat(Request $request, SommelierService $sommelierService, RateLimiterFactory $sommelierApiLimiter): JsonResponse
    {
        $limiter = $sommelierApiLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json(['error' => 'Trop de requêtes. Veuillez patienter avant de réessayer.'], 429);
        }

        $data = json_decode($request->getContent(), true);

        $message = trim($data['message'] ?? '');
        if ($message === '') {
            return $this->json(['error' => 'Message requis.'], 400);
        }

        if (mb_strlen($message) > 500) {
            return $this->json(['error' => 'Message trop long (500 caractères max).'], 400);
        }

        $history = $data['history'] ?? [];
        // Limiter l'historique à 10 messages pour ne pas surcharger le contexte
        $history = array_slice($history, -10);

        $response = $sommelierService->chat($message, $history);

        return $this->json(['response' => $response]);
    }
}
