<?php

declare(strict_types=1);

namespace App\Repository\Catalog;

use App\Entity\Catalog\GrapeVariety;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GrapeVariety>
 */
class GrapeVarietyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GrapeVariety::class);
    }

    /**
     * @return GrapeVariety[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('g')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?GrapeVariety
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
