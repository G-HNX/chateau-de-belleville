<?php

declare(strict_types=1);

namespace App\Repository\Catalog;

use App\Entity\Catalog\WineCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WineCategory>
 */
class WineCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WineCategory::class);
    }

    /**
     * @return WineCategory[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?WineCategory
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
