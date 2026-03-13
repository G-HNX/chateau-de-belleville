<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NewsletterSubscriber;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsletterSubscriber>
 */
class NewsletterSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsletterSubscriber::class);
    }

    /**
     * @return array<int, array{email: string, token: string}>
     */
    public function findAllEmails(): array
    {
        return $this->createQueryBuilder('s')
            ->select('s.email, s.unsubscribeToken AS token')
            ->getQuery()
            ->getArrayResult();
    }
}
