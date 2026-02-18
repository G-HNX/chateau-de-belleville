<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_legal')]
    public function index(): Response
    {
        return $this->render('legal/index.html.twig');
    }

    #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('legal/cgv.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('legal/privacy.html.twig');
    }

    #[Route('/livraison', name: 'app_shipping')]
    public function shipping(): Response
    {
        return $this->render('legal/shipping.html.twig');
    }
}
