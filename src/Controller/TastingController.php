<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\Tasting;
use App\Entity\Booking\TastingSlot;
use App\Repository\Booking\TastingRepository;
use App\Repository\Booking\TastingSlotRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/degustations')]
class TastingController extends AbstractController
{
    #[Route('', name: 'app_tasting_index')]
    public function index(TastingRepository $tastingRepository): Response
    {
        return $this->render('tasting/index.html.twig', [
            'tastings' => $tastingRepository->findAllActive(),
        ]);
    }

    #[Route('/{slug}', name: 'app_tasting_show')]
    public function show(
        Tasting $tasting,
        TastingSlotRepository $slotRepository,
    ): Response {
        if (!$tasting->isActive()) {
            throw $this->createNotFoundException('Cette formule n\'est pas disponible.');
        }

        $availableSlots = $slotRepository->findAvailableForTasting($tasting);

        return $this->render('tasting/show.html.twig', [
            'tasting' => $tasting,
            'availableSlots' => $availableSlots,
        ]);
    }

    #[Route('/{slug}/reserver/{slotId}', name: 'app_tasting_reserve', methods: ['GET', 'POST'])]
    public function reserve(
        Tasting $tasting,
        int $slotId,
        Request $request,
        TastingSlotRepository $slotRepository,
        EntityManagerInterface $em,
    ): Response {
        $slot = $slotRepository->find($slotId);

        if (!$slot || $slot->getTasting() !== $tasting || !$slot->isAvailable() || $slot->isPast()) {
            throw $this->createNotFoundException('Ce creneau n\'est pas disponible.');
        }

        $reservation = new Reservation();

        // Pre-remplir si l'utilisateur est connecte
        $user = $this->getUser();
        if ($user) {
            $reservation->setUser($user);
            $reservation->setFirstName($user->getFirstName());
            $reservation->setLastName($user->getLastName());
            $reservation->setEmail($user->getEmail());
            $reservation->setPhone($user->getPhone() ?? '');
        }

        if ($request->isMethod('POST')) {
            $reservation->setSlot($slot);
            $reservation->setFirstName($request->request->get('firstName', ''));
            $reservation->setLastName($request->request->get('lastName', ''));
            $reservation->setEmail($request->request->get('email', ''));
            $reservation->setPhone($request->request->get('phone', ''));
            $reservation->setNumberOfParticipants($request->request->getInt('numberOfParticipants', 2));
            $reservation->setMessage($request->request->get('message'));

            if (!$slot->canAccommodate($reservation->getNumberOfParticipants())) {
                $this->addFlash('error', 'Il n\'y a pas assez de places disponibles pour ce creneau.');

                return $this->redirectToRoute('app_tasting_show', ['slug' => $tasting->getSlug()]);
            }

            $em->persist($reservation);
            $em->flush();

            $this->addFlash('success', sprintf(
                'Votre reservation %s a bien ete enregistree. Vous recevrez une confirmation par email.',
                $reservation->getReference(),
            ));

            return $this->redirectToRoute('app_tasting_reservation_confirmation', [
                'reference' => $reservation->getReference(),
            ]);
        }

        return $this->render('tasting/reserve.html.twig', [
            'tasting' => $tasting,
            'slot' => $slot,
            'reservation' => $reservation,
        ]);
    }

    #[Route('/reservation/{reference}', name: 'app_tasting_reservation_confirmation', priority: 2)]
    public function confirmation(
        string $reference,
        \App\Repository\Booking\ReservationRepository $reservationRepository,
    ): Response {
        $reservation = $reservationRepository->findByReference($reference);

        if (!$reservation) {
            throw $this->createNotFoundException('Reservation introuvable.');
        }

        return $this->render('tasting/confirmation.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
