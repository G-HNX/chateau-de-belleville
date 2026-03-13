<?php

declare(strict_types=1);

namespace App\Repository\Domain;

use App\Entity\Domain\DomainPhoto;
use App\Enum\DomainSection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DomainPhoto>
 */
class DomainPhotoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DomainPhoto::class);
    }

    /**
     * @return DomainPhoto[]
     */
    public function findActiveBySection(DomainSection $section): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.section = :section')
            ->andWhere('p.isActive = true')
            ->setParameter('section', $section)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param DomainSection[] $sections
     * @return array<string, DomainPhoto[]>
     */
    public function findActiveBySections(array $sections): array
    {
        $photos = $this->createQueryBuilder('p')
            ->andWhere('p.section IN (:sections)')
            ->andWhere('p.isActive = true')
            ->setParameter('sections', $sections)
            ->orderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();

        $grouped = [];
        foreach ($photos as $photo) {
            $grouped[$photo->getSection()->value][] = $photo;
        }

        return $grouped;
    }
}
