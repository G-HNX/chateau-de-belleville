<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\DomainSection;
use App\Repository\Domain\DomainPhotoRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/domaine')]
class DomainController extends AbstractController
{
    public function __construct(
        private readonly DomainPhotoRepository $photoRepository,
    ) {}

    #[Route('', name: 'app_domain')]
    public function index(): Response
    {
        $photos = $this->photoRepository->findActiveBySections([
            DomainSection::HISTOIRE,
            DomainSection::TERROIR,
        ]);

        return $this->render('domain/index.html.twig', [
            'photosHistoire' => $photos[DomainSection::HISTOIRE->value] ?? [],
            'photosTerrroir' => $photos[DomainSection::TERROIR->value] ?? [],
        ]);
    }

    #[Route('/respect-de-la-nature', name: 'app_domain_nature')]
    public function nature(): Response
    {
        $photos = $this->photoRepository->findActiveBySections([
            DomainSection::NATURE,
            DomainSection::NATURE_BAS,
        ]);

        return $this->render('domain/nature.html.twig', [
            'photosNature'    => $photos[DomainSection::NATURE->value] ?? [],
            'photosNatureBas' => $photos[DomainSection::NATURE_BAS->value] ?? [],
        ]);
    }

    #[Route('/transmission', name: 'app_domain_transmission')]
    public function transmission(): Response
    {
        return $this->render('domain/transmission.html.twig', [
            'photosTransmission' => $this->photoRepository->findActiveBySection(DomainSection::TRANSMISSION),
        ]);
    }

    #[Route('/excellence', name: 'app_domain_excellence')]
    public function excellence(): Response
    {
        return $this->render('domain/excellence.html.twig', [
            'photosExcellence' => $this->photoRepository->findActiveBySection(DomainSection::EXCELLENCE),
        ]);
    }
}
