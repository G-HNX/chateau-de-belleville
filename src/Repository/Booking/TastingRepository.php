<?php

declare(strict_types=1);

namespace App\Repository\Booking;

use App\Entity\Booking\Tasting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tasting>
 */
class TastingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tasting::class);
    }

    /**
     * @return Tasting[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.priceInCents', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?Tasting
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
