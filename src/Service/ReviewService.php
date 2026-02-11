<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Customer\Review;
use App\Entity\User\User;
use App\Enum\OrderStatus;
use App\Repository\Customer\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * @return Review[]
     */
    public function getApprovedReviews(Wine $wine): array
    {
        return $this->reviewRepository->findApprovedByWine($wine);
    }

    public function hasUserReviewed(User $user, Wine $wine): bool
    {
        return (bool) $this->reviewRepository->findOneBy([
            'user' => $user,
            'wine' => $wine,
        ]);
    }

    public function hasUserPurchased(User $user, Wine $wine): bool
    {
        $result = $this->em->createQueryBuilder()
            ->select('COUNT(oi.id)')
            ->from('App\Entity\Order\OrderItem', 'oi')
            ->join('oi.order', 'o')
            ->where('o.customer = :user')
            ->andWhere('oi.wine = :wine')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('wine', $wine)
            ->setParameter('statuses', [
                OrderStatus::PAID->value,
                OrderStatus::PROCESSING->value,
                OrderStatus::SHIPPED->value,
                OrderStatus::DELIVERED->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result > 0;
    }

    /**
     * @return int[] IDs des utilisateurs ayant acheté ce vin
     */
    public function getPurchaserIds(Wine $wine): array
    {
        $rows = $this->em->createQueryBuilder()
            ->select('DISTINCT IDENTITY(o.customer) AS userId')
            ->from('App\Entity\Order\OrderItem', 'oi')
            ->join('oi.order', 'o')
            ->where('oi.wine = :wine')
            ->andWhere('o.customer IS NOT NULL')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('wine', $wine)
            ->setParameter('statuses', [
                OrderStatus::PAID->value,
                OrderStatus::PROCESSING->value,
                OrderStatus::SHIPPED->value,
                OrderStatus::DELIVERED->value,
            ])
            ->getQuery()
            ->getScalarResult();

        return array_map(fn (array $row) => (int) $row['userId'], $rows);
    }

    public function createReview(Review $review): void
    {
        $this->em->persist($review);
        $this->em->flush();
    }
}
