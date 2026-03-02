<?php

declare(strict_types=1);

namespace App\Repository\News;

use App\Entity\News\NewsArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsArticle>
 */
class NewsArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsArticle::class);
    }

    /**
     * @return NewsArticle[]
     */
    public function findPublished(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.isPublished = true')
            ->orderBy('n.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
