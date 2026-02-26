<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\Booking\TastingRepository;
use App\Repository\Catalog\WineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap')]
    public function index(WineRepository $wineRepository, TastingRepository $tastingRepository): Response
    {
        $wines = $wineRepository->findAllActive();
        $tastings = $tastingRepository->findAllActive();

        $response = $this->render('sitemap.xml.twig', [
            'wines' => $wines,
            'tastings' => $tastings,
        ]);

        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }
}
