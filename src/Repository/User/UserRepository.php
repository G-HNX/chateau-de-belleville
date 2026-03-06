<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Entity\User\User;
use App\Enum\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @return User[]
     */
    public function findAllCustomers(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles NOT LIKE :adminRole')
            ->setParameter('adminRole', '%"ROLE_ADMIN"%')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les clients (non-admin) avec leur nombre de commandes et total dépensé,
     * en excluant les commandes annulées et remboursées.
     *
     * @return string[]
     */
    public function findNewsletterOptInEmails(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('u.email')
            ->andWhere('u.newsletterOptIn = true')
            ->getQuery()
            ->getArrayResult();

        return array_column($result, 'email');
    }

    /**
     * @return array<int, array{0: User, orderCount: string, totalSpent: string}>
     */
    public function findForExport(): array
    {
        return $this->createQueryBuilder('u')
            ->addSelect('COUNT(o.id) AS orderCount, COALESCE(SUM(o.totalInCents), 0) AS totalSpent')
            ->leftJoin('u.orders', 'o', 'WITH', 'o.status NOT IN (:excluded)')
            ->setParameter('excluded', [OrderStatus::CANCELLED->value, OrderStatus::REFUNDED->value])
            ->andWhere('u.roles NOT LIKE :adminRole')
            ->setParameter('adminRole', '%"ROLE_ADMIN"%')
            ->groupBy('u.id')
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
