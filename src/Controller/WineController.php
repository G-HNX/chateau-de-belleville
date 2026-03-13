<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Catalog\Wine;
use App\Entity\Customer\Review;
use App\Form\ReviewType;
use App\Repository\Catalog\WineCategoryRepository;
use App\Repository\Catalog\WineRepository;
use App\Service\ReviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/vins')]
class WineController extends AbstractController
{
    #[Route('', name: 'app_wine_index')]
    public function index(
        Request $request,
        WineRepository $wineRepository,
        WineCategoryRepository $categoryRepository,
    ): Response {
        $filters = [];

        if ($categoryId = $request->query->getInt('categorie')) {
            $category = $categoryRepository->find($categoryId);
            if ($category) {
                $filters['category'] = $category;
            }
        }

        return $this->render('wine/index.html.twig', [
            'wines' => $wineRepository->findByFilters($filters),
            'categories' => $categoryRepository->findAllActive(),
            'currentFilters' => $filters,
        ]);
    }

    #[Route('/{slug}', name: 'app_wine_show')]
    public function show(Wine $wine, ReviewService $reviewService, WineRepository $wineRepository): Response
    {
        if (!$wine->isActive()) {
            throw $this->createNotFoundException('Ce vin n\'est pas disponible.');
        }

        $user = $this->getUser();
        $reviews = $reviewService->getApprovedReviews($wine);
        $hasReviewed = $user ? $reviewService->hasUserReviewed($user, $wine) : false;

        // Calcul côté serveur : IDs d'avis (pas d'utilisateurs) dont l'auteur a acheté le vin.
        // On n'expose jamais les user IDs au template pour éviter la fuite PII.
        $purchaserUserIds = $reviewService->getPurchaserIds($wine);
        $verifiedReviewIds = array_values(array_map(
            static fn ($r) => $r->getId(),
            array_filter($reviews, static fn ($r) => in_array($r->getUser()?->getId(), $purchaserUserIds, true))
        ));

        $reviewForm = null;
        if ($user && !$hasReviewed) {
            $reviewForm = $this->createForm(ReviewType::class, new Review(), [
                'action' => $this->generateUrl('app_review_add', ['slug' => $wine->getSlug()]),
            ]);
        }

        return $this->render('wine/show.html.twig', [
            'wine' => $wine,
            'reviews' => $reviews,
            'reviewForm' => $reviewForm,
            'hasReviewed' => $hasReviewed,
            'verifiedReviewIds' => $verifiedReviewIds,
            'similarWines' => $wineRepository->findSimilar($wine, 3),
        ]);
    }
}
