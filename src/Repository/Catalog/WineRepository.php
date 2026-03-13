<?php

declare(strict_types=1);

namespace App\Repository\Catalog;

use App\Entity\Catalog\Wine;
use App\Entity\Catalog\WineCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Wine>
 */
class WineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Wine::class);
    }

    /**
     * @return Wine[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.images', 'wi')
            ->addSelect('wi')
            ->andWhere('w.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Wine[]
     */
    public function findFeatured(int $limit = 6): array
    {
        return $this->createQueryBuilder('w')
            ->leftJoin('w.images', 'wi')
            ->addSelect('wi')
            ->andWhere('w.isActive = :active')
            ->andWhere('w.isFeatured = :featured')
            ->setParameter('active', true)
            ->setParameter('featured', true)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array<string, mixed> $filters
     * @return Wine[]
     */
    public function findByFilters(array $filters, string $sort = 'newest', int $page = 1, int $limit = 12): array
    {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.images', 'wi')
            ->addSelect('wi')
            ->andWhere('w.isActive = :active')
            ->setParameter('active', true);

        $this->applyFilters($qb, $filters);
        $this->applySort($qb, $sort);

        return $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Wine[]
     */
    public function findByCategory(WineCategory $category, string $sort = 'newest', int $page = 1, int $limit = 12): array
    {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.images', 'wi')
            ->addSelect('wi')
            ->andWhere('w.isActive = :active')
            ->andWhere('w.category = :category')
            ->setParameter('active', true)
            ->setParameter('category', $category);

        $this->applySort($qb, $sort);

        return $qb
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Wine[]
     */
    /**
     * @return Wine[]
     */
    public function findSimilar(Wine $wine, int $limit = 3): array
    {
        $qb = $this->createQueryBuilder('w')
            ->leftJoin('w.images', 'wi')
            ->addSelect('wi')
            ->andWhere('w.isActive = :active')
            ->andWhere('w.id != :id')
            ->setParameter('active', true)
            ->setParameter('id', $wine->getId());

        if ($wine->getCategory()) {
            $qb->andWhere('w.category = :category')
               ->setParameter('category', $wine->getCategory());
        }

        $results = $qb->getQuery()->getResult();
        shuffle($results);
        $results = array_slice($results, 0, $limit);

        // Si pas assez de vins dans la même catégorie, compléter avec d'autres vins
        if (count($results) < $limit) {
            $ids = array_merge([$wine->getId()], array_map(fn ($w) => $w->getId(), $results));
            $extra = $this->createQueryBuilder('w')
                ->leftJoin('w.images', 'wi')
                ->addSelect('wi')
                ->andWhere('w.isActive = :active')
                ->andWhere('w.id NOT IN (:ids)')
                ->setParameter('active', true)
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
            shuffle($extra);
            $results = array_merge($results, array_slice($extra, 0, $limit - count($results)));
        }

        return $results;
    }

    public function findBySlug(string $slug): ?Wine
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function countByFilters(array $filters): int
    {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.isActive = :active')
            ->setParameter('active', true);

        $this->applyFilters($qb, $filters);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int[]
     */
    public function findDistinctVintages(): array
    {
        $result = $this->createQueryBuilder('w')
            ->select('DISTINCT w.vintage')
            ->andWhere('w.isActive = :active')
            ->andWhere('w.vintage IS NOT NULL')
            ->setParameter('active', true)
            ->orderBy('w.vintage', 'DESC')
            ->getQuery()
            ->getSingleColumnResult();

        return array_filter($result, fn ($v) => $v !== null);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['category'])) {
            $qb->andWhere('w.category = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['appellation'])) {
            $qb->andWhere('w.appellation = :appellation')
               ->setParameter('appellation', $filters['appellation']);
        }

        if (!empty($filters['vintage'])) {
            $qb->andWhere('w.vintage = :vintage')
               ->setParameter('vintage', $filters['vintage']);
        }

        if (!empty($filters['priceMin'])) {
            $qb->andWhere('w.priceInCents >= :priceMin')
               ->setParameter('priceMin', $filters['priceMin'] * 100);
        }

        if (!empty($filters['priceMax'])) {
            $qb->andWhere('w.priceInCents <= :priceMax')
               ->setParameter('priceMax', $filters['priceMax'] * 100);
        }

        if (!empty($filters['inStock'])) {
            $qb->andWhere('w.stock > 0');
        }
    }

    private function applySort(QueryBuilder $qb, string $sort): void
    {
        match ($sort) {
            'price_asc' => $qb->orderBy('w.priceInCents', 'ASC'),
            'price_desc' => $qb->orderBy('w.priceInCents', 'DESC'),
            'name' => $qb->orderBy('w.name', 'ASC'),
            default => $qb->orderBy('w.createdAt', 'DESC'),
        };
    }
}
