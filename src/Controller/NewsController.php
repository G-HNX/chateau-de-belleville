<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\News\NewsArticle;
use App\Repository\News\NewsArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/actualites')]
class NewsController extends AbstractController
{
    #[Route('', name: 'app_news_index')]
    public function index(NewsArticleRepository $newsArticleRepository): Response
    {
        return $this->render('news/index.html.twig', [
            'articles' => $newsArticleRepository->findPublished(),
        ]);
    }

    #[Route('/{slug}', name: 'app_news_show')]
    public function show(NewsArticle $article): Response
    {
        if (!$article->isPublished()) {
            throw $this->createNotFoundException('Cet article n\'est pas disponible.');
        }

        return $this->render('news/show.html.twig', [
            'article' => $article,
        ]);
    }
}
