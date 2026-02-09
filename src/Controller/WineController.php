<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Catalog\Wine;
use App\Enum\WineType;
use App\Repository\Catalog\WineCategoryRepository;
use App\Repository\Catalog\WineRepository;
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

        if ($type = $request->query->get('type')) {
            $wineType = WineType::tryFrom($type);
            if ($wineType) {
                $filters['type'] = $wineType;
            }
        }

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
            'wineTypes' => WineType::cases(),
            'currentFilters' => $filters,
            'currentSort' => $sort,
            'currentPage' => $page,
        ]);
    }

    #[Route('/{slug}', name: 'app_wine_show')]
    public function show(Wine $wine): Response
    {
        if (!$wine->isActive()) {
            throw $this->createNotFoundException('Ce vin n\'est pas disponible.');
        }

        return $this->render('wine/show.html.twig', [
            'wine' => $wine,
        ]);
    }
}
