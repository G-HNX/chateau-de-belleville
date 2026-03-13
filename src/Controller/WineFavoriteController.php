<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Catalog\Wine;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class WineFavoriteController extends AbstractController
{
    #[Route('/vins/{id}/favori', name: 'app_wine_favorite_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggle(Wine $wine, Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$this->isCsrfTokenValid('favorite_' . $wine->getId(), $request->request->get('_token'))) {
            return $this->json(['success' => false, 'message' => 'Token invalide.'], 403);
        }

        /** @var User $user */
        $user = $this->getUser();

        if ($user->isFavoriteWine($wine)) {
            $user->removeFavoriteWine($wine);
            $isFavorite = false;
            $message = $wine->getName() . ' retiré de vos vins.';
        } else {
            $user->addFavoriteWine($wine);
            $isFavorite = true;
            $message = $wine->getName() . ' ajouté à vos vins.';
        }

        $em->flush();

        return $this->json([
            'success' => true,
            'isFavorite' => $isFavorite,
            'message' => $message,
        ]);
    }
}
