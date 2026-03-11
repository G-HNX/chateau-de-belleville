<?php

declare(strict_types=1);

namespace App\Repository\Booking;

use App\Entity\Booking\Reservation;
use App\Entity\User\User;
use App\Enum\ReservationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findByReference(string $reference): ?Reservation
    {
        return $this->findOneBy(['reference' => $reference]);
    }

    /**
     * @return Reservation[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Reservation[]
     */
    public function findByStatus(ReservationStatus $status): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :status')
            ->setParameter('status', $status)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Nombre de réservations par mois sur les N derniers mois.
     *
     * @return array<int, array{month: string, count: int, participants: int}>
     */
    public function getMonthlyCount(int $months = 12): array
    {
        $from = new \DateTime("first day of -{$months} months midnight");

        $rows = $this->createQueryBuilder('r')
            ->select("SUBSTRING(r.createdAt, 1, 7) AS month, COUNT(r.id) AS cnt, SUM(r.numberOfParticipants) AS participants")
            ->andWhere('r.createdAt >= :from')
            ->andWhere('r.status NOT IN (:excluded)')
            ->setParameter('from', $from)
            ->setParameter('excluded', [ReservationStatus::CANCELLED])
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn (array $r) => [
            'month' => $r['month'],
            'count' => (int) $r['cnt'],
            'participants' => (int) $r['participants'],
        ], $rows);
    }

    /**
     * @return Reservation[]
     */
    public function findUpcoming(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.slot', 's')
            ->andWhere('s.date >= :today')
            ->andWhere('r.status IN (:activeStatuses)')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('activeStatuses', [ReservationStatus::PENDING, ReservationStatus::CONFIRMED])
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
