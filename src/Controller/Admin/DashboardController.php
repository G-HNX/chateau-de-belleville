<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\Tasting;
use App\Entity\Booking\TastingSlot;
use App\Entity\Catalog\Appellation;
use App\Entity\Catalog\FoodPairing;
use App\Entity\Catalog\GrapeVariety;
use App\Entity\Catalog\Wine;
use App\Entity\Catalog\WineCategory;
use App\Entity\Catalog\WineImage;
use App\Entity\Customer\Review;
use App\Entity\Domain\DomainPhoto;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\User\User;
use App\Enum\OrderStatus;
use App\Enum\ReservationStatus;
use App\Repository\Booking\ReservationRepository;
use App\Repository\Catalog\WineRepository;
use App\Repository\Order\OrderItemRepository;
use App\Repository\Order\OrderRepository;
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
        /** @var OrderRepository $orderRepo */
        $orderRepo = $this->em->getRepository(Order::class);
        /** @var OrderItemRepository $orderItemRepo */
        $orderItemRepo = $this->em->getRepository(OrderItem::class);
        /** @var WineRepository $wineRepo */
        $wineRepo = $this->em->getRepository(Wine::class);
        /** @var ReservationRepository $reservationRepo */
        $reservationRepo = $this->em->getRepository(Reservation::class);

        $reviewRepo = $this->em->getRepository(Review::class);
        $userRepo = $this->em->getRepository(User::class);

        // --- KPIs principaux ---
        $totalOrders = $orderRepo->count([]);
        $pendingOrders = $orderRepo->count(['status' => OrderStatus::PENDING]);
        $processingOrders = $orderRepo->count(['status' => OrderStatus::PROCESSING]);
        $totalRevenue = $orderRepo->getTotalRevenue();
        $monthStart = new \DateTime('first day of this month midnight');
        $monthRevenue = $orderRepo->getTotalRevenue($monthStart);
        $averageOrder = $orderRepo->getAverageOrderValue();

        // --- Commandes par statut ---
        $ordersByStatus = $orderRepo->countByStatus();

        // --- CA mensuel (12 derniers mois) ---
        $monthlyRevenue = $orderRepo->getMonthlyRevenue(12);

        // --- Top vins vendus ---
        $topWines = $orderItemRepo->getTopSellingWines(10);

        // --- Stocks ---
        $lowStockThreshold = 10;
        $lowStockWines = $wineRepo->createQueryBuilder('w')
            ->andWhere('w.isActive = true')
            ->andWhere('w.stock <= :threshold')
            ->setParameter('threshold', $lowStockThreshold)
            ->orderBy('w.stock', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $outOfStockCount = (int) $wineRepo->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.isActive = true')
            ->andWhere('w.stock = 0')
            ->getQuery()
            ->getSingleScalarResult();

        $totalStockValue = (int) ($wineRepo->createQueryBuilder('w')
            ->select('SUM(w.priceInCents * w.stock)')
            ->andWhere('w.isActive = true')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        // --- Dégustations les plus demandées ---
        $popularTastings = $reservationRepo->createQueryBuilder('r')
            ->select('t.name AS tastingName, COUNT(r.id) AS reservationCount, SUM(r.numberOfParticipants) AS totalParticipants')
            ->join('r.slot', 's')
            ->join('s.tasting', 't')
            ->andWhere('r.status IN (:active)')
            ->setParameter('active', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
            ->groupBy('t.name')
            ->orderBy('reservationCount', 'DESC')
            ->getQuery()
            ->getResult();

        // --- Réservations à venir ---
        $upcomingReservations = array_slice($reservationRepo->findUpcoming(), 0, 8);

        // --- Dernières commandes ---
        $recentOrders = $orderRepo->findBy([], ['createdAt' => 'DESC'], 8);

        // --- Avis en attente ---
        $pendingReviews = $reviewRepo->count(['isApproved' => false]);
        $recentReviews = $reviewRepo->findBy(['isApproved' => false], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'totalOrders' => $totalOrders,
            'pendingOrders' => $pendingOrders,
            'processingOrders' => $processingOrders,
            'totalRevenue' => $totalRevenue,
            'monthRevenue' => $monthRevenue,
            'averageOrder' => $averageOrder,
            'ordersByStatus' => $ordersByStatus,
            'monthlyRevenue' => $monthlyRevenue,
            'topWines' => $topWines,
            'lowStockWines' => $lowStockWines,
            'outOfStockCount' => $outOfStockCount,
            'totalStockValue' => $totalStockValue,
            'popularTastings' => $popularTastings,
            'upcomingReservations' => $upcomingReservations,
            'pendingReviews' => $pendingReviews,
            'pendingReservations' => $reservationRepo->count(['status' => ReservationStatus::PENDING]),
            'totalUsers' => $userRepo->count([]),
            'totalWines' => $wineRepo->count([]),
            'recentOrders' => $recentOrders,
            'recentReviews' => $recentReviews,
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
        yield MenuItem::linkToUrl('Export CSV', 'fa fa-download', '/admin/orders/export.csv');
        yield MenuItem::linkToCrud('Avis clients', 'fa fa-star', Review::class);

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Vins', 'fa fa-wine-glass', Wine::class);
        yield MenuItem::linkToCrud('Images', 'fa fa-image', WineImage::class);
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', WineCategory::class);
        yield MenuItem::linkToCrud('Appellations', 'fa fa-map-marker', Appellation::class);
        yield MenuItem::linkToCrud('Cépages', 'fa fa-leaf', GrapeVariety::class);
        yield MenuItem::linkToCrud('Accords mets-vins', 'fa fa-utensils', FoodPairing::class);

        yield MenuItem::section('Dégustations');
        yield MenuItem::linkToCrud('Formules', 'fa fa-glass-cheers', Tasting::class);
        yield MenuItem::linkToCrud('Créneaux', 'fa fa-clock', TastingSlot::class);
        yield MenuItem::linkToCrud('Réservations', 'fa fa-calendar-check', Reservation::class);

        yield MenuItem::section('Contenu du site');
        yield MenuItem::linkToCrud('Photos du domaine', 'fa fa-camera', DomainPhoto::class);

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', User::class);
        yield MenuItem::linkToUrl('Export CSV', 'fa fa-download', '/admin/customers/export.csv');

        yield MenuItem::section('');
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-external-link-alt', '/');
    }
}
