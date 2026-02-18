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

        if ($priceMin = $request->query->get('prix_min')) {
            $filters['priceMin'] = (float) $priceMin;
        }

        if ($priceMax = $request->query->get('prix_max')) {
            $filters['priceMax'] = (float) $priceMax;
        }

        $sort = $request->query->get('tri', 'newest');
        $page = max(1, $request->query->getInt('page', 1));

        return $this->render('wine/index.html.twig', [
            'wines' => $wineRepository->findByFilters($filters, $sort, $page),
            'categories' => $categoryRepository->findAll(),
            'currentFilters' => $filters,
            'currentSort' => $sort,
            'currentPage' => $page,
        ]);
    }

    #[Route('/{slug}', name: 'app_wine_show')]
    public function show(Wine $wine, ReviewService $reviewService): Response
    {
        if (!$wine->isActive()) {
            throw $this->createNotFoundException('Ce vin n\'est pas disponible.');
        }

        $user = $this->getUser();
        $reviews = $reviewService->getApprovedReviews($wine);
        $hasReviewed = $user ? $reviewService->hasUserReviewed($user, $wine) : false;
        $purchaserIds = $reviewService->getPurchaserIds($wine);

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
            'purchaserIds' => $purchaserIds,
        ]);
    }
}
