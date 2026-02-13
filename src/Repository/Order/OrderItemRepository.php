<?php

declare(strict_types=1);

namespace App\Repository\Order;

use App\Entity\Order\OrderItem;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrderItem>
 */
class OrderItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrderItem::class);
    }

    /**
     * Top des vins les plus vendus (par quantite).
     *
     * @return array<int, array{wineName: string, wineVintage: ?int, totalQuantity: int, totalRevenue: int}>
     */
    public function getTopSellingWines(int $limit = 10): array
    {
        $rows = $this->createQueryBuilder('oi')
            ->select('oi.wineName, oi.wineVintage, SUM(oi.quantity) AS totalQuantity, SUM(oi.unitPriceInCents * oi.quantity) AS totalRevenue')
            ->join('oi.order', 'o')
            ->andWhere('o.status NOT IN (:excluded)')
            ->setParameter('excluded', [OrderStatus::CANCELLED, OrderStatus::REFUNDED])
            ->groupBy('oi.wineName, oi.wineVintage')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_map(fn (array $r) => [
            'wineName' => $r['wineName'],
            'wineVintage' => $r['wineVintage'],
            'totalQuantity' => (int) $r['totalQuantity'],
            'totalRevenue' => (int) $r['totalRevenue'],
        ], $rows);
    }
}
