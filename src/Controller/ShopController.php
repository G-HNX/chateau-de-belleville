<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\Catalog\AppellationRepository;
use App\Repository\Catalog\WineCategoryRepository;
use App\Repository\Catalog\WineRepository;
use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ShopController extends AbstractController
{
    private const FREE_SHIPPING_THRESHOLD = 150_00; // centimes

    #[Route('/boutique', name: 'app_shop_index')]
    public function index(
        Request $request,
        WineRepository $wineRepository,
        WineCategoryRepository $categoryRepository,
        AppellationRepository $appellationRepository,
        CartService $cartService,
    ): Response {
        $filters = [];

        if ($categoryId = $request->query->getInt('categorie')) {
            $category = $categoryRepository->find($categoryId);
            if ($category) {
                $filters['category'] = $category;
            }
        }

        if ($appellationId = $request->query->getInt('appellation')) {
            $appellation = $appellationRepository->find($appellationId);
            if ($appellation) {
                $filters['appellation'] = $appellation;
            }
        }

        if ($vintage = $request->query->getInt('millesime')) {
            $filters['vintage'] = $vintage;
        }

        if ($priceMin = $request->query->get('prix_min')) {
            $filters['priceMin'] = (float) $priceMin;
        }

        if ($priceMax = $request->query->get('prix_max')) {
            $filters['priceMax'] = (float) $priceMax;
        }

        if ($request->query->get('en_stock')) {
            $filters['inStock'] = true;
        }

        $sort = $request->query->get('tri', 'newest');

        $wines = $wineRepository->findByFilters($filters, $sort, 1, 200);
        $cart  = $cartService->getCart($this->getUser());

        $cartTotalCents = $cart ? array_sum(
            array_map(fn ($item) => $item->getTotalInCents(), $cart->getItems()->toArray())
        ) : 0;

        return $this->render('shop/index.html.twig', [
            'wines'                   => $wines,
            'categories'              => $categoryRepository->findAll(),
            'appellations'            => $appellationRepository->findBy([], ['name' => 'ASC']),
            'vintages'                => $wineRepository->findDistinctVintages(),
            'cart'                    => $cart,
            'cartTotalCents'          => $cartTotalCents,
            'freeShippingThreshold'   => self::FREE_SHIPPING_THRESHOLD,
            'remainingCents'          => max(0, self::FREE_SHIPPING_THRESHOLD - $cartTotalCents),
            'freeShippingProgressPct' => min(100, (int) ($cartTotalCents / self::FREE_SHIPPING_THRESHOLD * 100)),
            'currentFilters'          => $filters,
            'currentSort'             => $sort,
        ]);
    }
}
