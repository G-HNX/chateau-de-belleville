<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\SommelierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class SommelierController extends AbstractController
{
    #[Route('/api/sommelier', name: 'app_sommelier_chat', methods: ['POST'])]
    public function chat(Request $request, SommelierService $sommelierService, RateLimiterFactoryInterface $sommelierApiLimiter): JsonResponse
    {
        $limiter = $sommelierApiLimiter->create($request->getClientIp() ?? '0.0.0.0');
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

        $rawHistory = $data['history'] ?? [];
        // Assainir l'historique : seuls les rôles "user"/"assistant" sont acceptés,
        // le contenu est limité à 1000 caractères pour éviter l'injection de prompt.
        $history = [];
        foreach (array_slice($rawHistory, -10) as $entry) {
            if (!isset($entry['role'], $entry['content'])) {
                continue;
            }
            if (!in_array($entry['role'], ['user', 'assistant'], true)) {
                continue;
            }
            $history[] = [
                'role' => $entry['role'],
                'content' => mb_substr((string) $entry['content'], 0, 1000),
            ];
        }

        $response = $sommelierService->chat($message, $history);

        return $this->json(['response' => $response]);
    }
}
