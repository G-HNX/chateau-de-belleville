<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Catalog\Wine;
use App\Form\ReviewType;
use App\Entity\Customer\Review;
use App\Service\ReviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewService $reviewService,
    ) {}

    #[Route('/vins/{slug}/avis', name: 'app_review_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(Wine $wine, Request $request): Response
    {
        $user = $this->getUser();

        if ($this->reviewService->hasUserReviewed($user, $wine)) {
            $this->addFlash('warning', 'Vous avez déjà laissé un avis pour ce vin.');

            return $this->redirectToRoute('app_wine_show', ['slug' => $wine->getSlug()]);
        }

        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setUser($user);
            $review->setWine($wine);

            $this->reviewService->createReview($review);

            $this->addFlash('success', 'Merci pour votre avis ! Il sera publié après modération.');

            return $this->redirectToRoute('app_wine_show', ['slug' => $wine->getSlug()]);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre avis.');

        return $this->redirectToRoute('app_wine_show', ['slug' => $wine->getSlug()]);
    }
}
