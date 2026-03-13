<?php

declare(strict_types=1);

namespace App\Repository\Catalog;

use App\Entity\Catalog\FoodPairing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FoodPairing>
 */
class FoodPairingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FoodPairing::class);
    }

    /**
     * @return FoodPairing[]
     */
    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?FoodPairing
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
