<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\Booking\TastingRepository;
use App\Repository\Catalog\WineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(WineRepository $wineRepository, TastingRepository $tastingRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'featuredWines' => $wineRepository->findFeatured(3),
            'tastings' => $tastingRepository->findAllActive(),
        ]);
    }
}
