<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\TastingSlot;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;

class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Pre-remplit la reservation avec les infos de l'utilisateur connecte.
     */
    public function prefillFromUser(Reservation $reservation, ?User $user): void
    {
        if (!$user) {
            return;
        }

        $reservation->setUser($user);
        $reservation->setFirstName($user->getFirstName());
        $reservation->setLastName($user->getLastName());
        $reservation->setEmail($user->getEmail());
        $reservation->setPhone($user->getPhone() ?? '');
    }

    /**
     * Finalise et persiste une reservation.
     * Retourne null si OK, ou un message d'erreur.
     */
    public function createReservation(Reservation $reservation, TastingSlot $slot): ?string
    {
        $reservation->setSlot($slot);

        if (!$slot->canAccommodate($reservation->getNumberOfParticipants())) {
            return 'Il n\'y a pas assez de places disponibles pour ce créneau.';
        }

        $this->em->persist($reservation);
        $this->em->flush();

        return null;
    }
}
