<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\Tasting;
use App\Entity\Catalog\Appellation;
use App\Entity\Catalog\Wine;
use App\Entity\Catalog\WineCategory;
use App\Entity\Customer\Review;
use App\Entity\Order\Order;
use App\Entity\User\User;
use App\Enum\OrderStatus;
use App\Enum\ReservationStatus;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    public function index(): Response
    {
        $orderRepo = $this->em->getRepository(Order::class);
        $reviewRepo = $this->em->getRepository(Review::class);
        $userRepo = $this->em->getRepository(User::class);
        $wineRepo = $this->em->getRepository(Wine::class);
        $reservationRepo = $this->em->getRepository(Reservation::class);

        return $this->render('admin/dashboard.html.twig', [
            'totalOrders' => $orderRepo->count([]),
            'pendingOrders' => $orderRepo->count(['status' => OrderStatus::PENDING]),
            'pendingReviews' => $reviewRepo->count(['isApproved' => false]),
            'totalUsers' => $userRepo->count([]),
            'totalWines' => $wineRepo->count([]),
            'lowStockWines' => $wineRepo->count([]),
            'pendingReservations' => $reservationRepo->count(['status' => ReservationStatus::PENDING]),
            'recentOrders' => $orderRepo->findBy([], ['createdAt' => 'DESC'], 5),
            'recentReviews' => $reviewRepo->findBy(['isApproved' => false], ['createdAt' => 'DESC'], 5),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Château de Belleville')
            ->setFaviconPath('images/logo.jpg')
            ->setLocales(['fr']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Boutique');
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Order::class);
        yield MenuItem::linkToCrud('Avis clients', 'fa fa-star', Review::class);

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Vins', 'fa fa-wine-glass', Wine::class);
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', WineCategory::class);
        yield MenuItem::linkToCrud('Appellations', 'fa fa-map-marker', Appellation::class);

        yield MenuItem::section('Dégustations');
        yield MenuItem::linkToCrud('Formules', 'fa fa-glass-cheers', Tasting::class);
        yield MenuItem::linkToCrud('Réservations', 'fa fa-calendar-check', Reservation::class);

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', User::class);

        yield MenuItem::section('');
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-external-link-alt', '/');
    }
}
