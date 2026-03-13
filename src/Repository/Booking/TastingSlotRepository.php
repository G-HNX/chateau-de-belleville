<?php

declare(strict_types=1);

namespace App\Repository\Booking;

use App\Entity\Booking\Tasting;
use App\Entity\Booking\TastingSlot;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TastingSlot>
 */
class TastingSlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TastingSlot::class);
    }

    /**
     * @return TastingSlot[]
     */
    public function findAvailableForTasting(Tasting $tasting, ?\DateTimeInterface $fromDate = null): array
    {
        $fromDate = $fromDate ?? new \DateTime('today');

        return $this->createQueryBuilder('s')
            ->leftJoin('s.reservations', 'r')
            ->addSelect('r')
            ->andWhere('s.tasting = :tasting')
            ->andWhere('s.date >= :fromDate')
            ->andWhere('s.isAvailable = :available')
            ->setParameter('tasting', $tasting)
            ->setParameter('fromDate', $fromDate)
            ->setParameter('available', true)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return TastingSlot[]
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.date = :date')
            ->andWhere('s.isAvailable = :available')
            ->setParameter('date', $date)
            ->setParameter('available', true)
            ->orderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
