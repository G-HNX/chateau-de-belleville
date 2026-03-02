<?php

declare(strict_types=1);

namespace App\Repository\Order;

use App\Entity\Order\Order;
use App\Entity\User\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findByReference(string $reference): ?Order
    {
        return $this->findOneBy(['reference' => $reference]);
    }

    /**
     * @return Order[]
     */
    public function findByCustomer(User $customer): array
    {
        return $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->andWhere('o.customer = :customer')
            ->setParameter('customer', $customer)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findByStatus(OrderStatus $status): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Order[]
     */
    public function findRecent(int $limit = 10): array
    {
        return $this->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le chiffre d'affaires sur une periode.
     */
    public function getTotalRevenue(\DateTimeInterface $from = null, \DateTimeInterface $to = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('SUM(o.totalInCents)')
            ->andWhere('o.status NOT IN (:excludedStatuses)')
            ->setParameter('excludedStatuses', [OrderStatus::CANCELLED, OrderStatus::REFUNDED]);

        if ($from) {
            $qb->andWhere('o.createdAt >= :from')
               ->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('o.createdAt <= :to')
               ->setParameter('to', $to);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Nombre de commandes par statut.
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status, COUNT(o.id) AS cnt')
            ->groupBy('o.status')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $status = $row['status'] instanceof OrderStatus ? $row['status'] : OrderStatus::from($row['status']);
            $result[$status->value] = (int) $row['cnt'];
        }

        return $result;
    }

    /**
     * CA par mois sur les N derniers mois.
     *
     * @return array<int, array{month: string, revenue: int, count: int}>
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $from = new \DateTime("first day of -{$months} months midnight");

        $rows = $this->createQueryBuilder('o')
            ->select("SUBSTRING(o.createdAt, 1, 7) AS month, SUM(o.totalInCents) AS revenue, COUNT(o.id) AS cnt")
            ->andWhere('o.createdAt >= :from')
            ->andWhere('o.status NOT IN (:excluded)')
            ->setParameter('from', $from)
            ->setParameter('excluded', [OrderStatus::CANCELLED, OrderStatus::REFUNDED])
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (array $r) => [
            'month' => $r['month'],
            'revenue' => (int) $r['revenue'],
            'count' => (int) $r['cnt'],
        ], $rows);
    }

    /**
     * Panier moyen (en centimes).
     */
    public function getAverageOrderValue(\DateTimeInterface $from = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('AVG(o.totalInCents)')
            ->andWhere('o.status NOT IN (:excluded)')
            ->setParameter('excluded', [OrderStatus::CANCELLED, OrderStatus::REFUNDED]);

        if ($from) {
            $qb->andWhere('o.createdAt >= :from')
               ->setParameter('from', $from);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Retourne toutes les commandes pour l'export CSV, avec les items eager-loadés.
     *
     * @return Order[]
     */
    public function findForExport(?\DateTime $from, ?\DateTime $to, ?OrderStatus $status): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->orderBy('o.createdAt', 'DESC');

        if ($from) {
            $qb->andWhere('o.createdAt >= :from')->setParameter('from', $from);
        }

        if ($to) {
            $qb->andWhere('o.createdAt <= :to')->setParameter('to', $to);
        }

        if ($status) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status->value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Retourne les commandes dont la date de naissance client doit être anonymisée (RGPD).
     *
     * @return Order[]
     */
    public function findOrdersWithBirthDateBefore(\DateTimeInterface $cutoff): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.customerBirthDate IS NOT NULL')
            ->andWhere('o.createdAt < :cutoff')
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getResult();
    }
}
