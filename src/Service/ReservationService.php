<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\TastingSlot;
use App\Entity\User\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ReservationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly EmailService $emailService,
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

        $error = null;
        $this->em->wrapInTransaction(function () use ($reservation, $slot, &$error): void {
            // Verrou pessimiste : empêche la surréservation simultanée
            $this->em->lock($slot, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($slot);

            if (!$slot->canAccommodate($reservation->getNumberOfParticipants())) {
                $error = 'Il n\'y a pas assez de places disponibles pour ce créneau.';

                return;
            }

            // Snapshot du prix au moment de la réservation
            $reservation->setPricePerPersonInCents($slot->getTasting()->getPriceInCents());

            $this->em->persist($reservation);
        });

        if ($error === null) {
            try {
                $this->emailService->sendReservationConfirmation($reservation);
            } catch (TransportExceptionInterface) {
                // Email non bloquant : la réservation est bien enregistrée même si l'email échoue
            }
        }

        return $error;
    }
}
