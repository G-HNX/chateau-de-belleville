<?php

declare(strict_types=1);

namespace App\Repository\Order;

use App\Entity\Order\Cart;
use App\Entity\User\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 */
class CartRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cart::class);
    }

    public function findByUser(User $user): ?Cart
    {
        return $this->findOneBy(['user' => $user]);
    }

    public function findBySessionId(string $sessionId): ?Cart
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    /**
     * Supprime les paniers anonymes non modifies depuis X jours.
     */
    public function removeExpiredAnonymousCarts(int $days = 7): int
    {
        $expireDate = new \DateTime("-{$days} days");

        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.user IS NULL')
            ->andWhere('c.updatedAt < :expireDate')
            ->setParameter('expireDate', $expireDate)
            ->getQuery()
            ->execute();
    }
}
