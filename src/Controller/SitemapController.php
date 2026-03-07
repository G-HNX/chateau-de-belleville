<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\Booking\TastingRepository;
use App\Repository\Catalog\WineRepository;
use App\Repository\News\NewsArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(
        WineRepository $wineRepository,
        TastingRepository $tastingRepository,
        NewsArticleRepository $newsRepository,
    ): Response {
        $urls = [];

        // Pages statiques avec priorités
        $staticPages = [
            ['route' => 'app_home', 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['route' => 'app_shop', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'app_tasting_index', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['route' => 'app_domain_excellence', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'app_domain_nature', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'app_domain_transmission', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'app_contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['route' => 'app_news', 'priority' => '0.7', 'changefreq' => 'weekly'],
            ['route' => 'app_legal', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'app_cgv', 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['route' => 'app_privacy', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach ($staticPages as $page) {
            $urls[] = [
                'loc' => $this->generateUrl($page['route'], [], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority'],
            ];
        }

        // Fiches vins
        foreach ($wineRepository->findAllActive() as $wine) {
            $urls[] = [
                'loc' => $this->generateUrl('app_wine_show', ['slug' => $wine->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        // Dégustations
        foreach ($tastingRepository->findAllActive() as $tasting) {
            $urls[] = [
                'loc' => $this->generateUrl('app_tasting_show', ['slug' => $tasting->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // Actualités
        foreach ($newsRepository->findPublished() as $article) {
            $urls[] = [
                'loc' => $this->generateUrl('app_news_show', ['slug' => $article->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
                'changefreq' => 'monthly',
                'priority' => '0.6',
            ];
        }

        $response = new Response(
            $this->renderView('sitemap.xml.twig', ['urls' => $urls]),
            200,
            ['Content-Type' => 'text/xml']
        );
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
