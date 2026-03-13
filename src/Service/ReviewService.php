<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Customer\Review;
use App\Entity\User\User;
use App\Enum\OrderStatus;
use App\Repository\Customer\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion des avis clients sur les vins.
 *
 * Fournit les méthodes pour récupérer les avis approuvés, vérifier si un
 * utilisateur a déjà donné son avis ou acheté un vin, et gérer le cycle
 * de vie des avis (création, suppression).
 */
class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Récupère tous les avis approuvés (modérés) pour un vin donné.
     *
     * @return Review[]
     */
    public function getApprovedReviews(Wine $wine): array
    {
        return $this->reviewRepository->findApprovedByWine($wine);
    }

    /**
     * Vérifie si l'utilisateur a déjà laissé un avis sur ce vin.
     */
    public function hasUserReviewed(User $user, Wine $wine): bool
    {
        return (bool) $this->reviewRepository->findOneBy([
            'user' => $user,
            'wine' => $wine,
        ]);
    }

    /**
     * Vérifie si l'utilisateur a acheté ce vin (commande payée, en cours, expédiée ou livrée).
     * Condition requise pour pouvoir laisser un avis.
     */
    public function hasUserPurchased(User $user, Wine $wine): bool
    {
        // Requête DQL : compte les articles commandés correspondant au vin pour cet utilisateur
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
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
            ])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result > 0;
    }

    /**
     * Récupère les IDs des utilisateurs ayant acheté ce vin.
     * Utilisé pour afficher le badge "achat vérifié" sur les avis.
     *
     * @return int[] IDs des utilisateurs acheteurs
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
                OrderStatus::PAID,
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
            ])
            ->getQuery()
            ->getScalarResult();

        return array_map(fn (array $row) => (int) $row['userId'], $rows);
    }

    /**
     * Persiste un nouvel avis en base de données.
     */
    public function createReview(Review $review): void
    {
        $this->em->persist($review);
        $this->em->flush();
    }

    /**
     * Supprime un avis de la base de données.
     */
    public function deleteReview(Review $review): void
    {
        $this->em->remove($review);
        $this->em->flush();
    }
}
