<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\Tasting;
use App\Form\ReservationType;
use App\Repository\Booking\ReservationRepository;
use App\Repository\Booking\TastingRepository;
use App\Repository\Booking\TastingSlotRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/degustations')]
class TastingController extends AbstractController
{
    public function __construct(
        private readonly ReservationService $reservationService,
    ) {}

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

        $slotsJson = json_encode(array_map(fn($slot) => [
            'id' => $slot->getId(),
            'date' => $slot->getDate()->format('Y-m-d'),
            'startTime' => $slot->getStartTime()->format('H:i'),
            'endTime' => $slot->getEndTime()->format('H:i'),
            'remainingSpots' => $slot->getRemainingSpots(),
        ], $availableSlots));

        return $this->render('tasting/show.html.twig', [
            'tasting' => $tasting,
            'availableSlots' => $availableSlots,
            'slotsJson' => $slotsJson,
        ]);
    }

    #[Route('/{slug}/reserver/{slotId}', name: 'app_tasting_reserve', methods: ['GET', 'POST'])]
    public function reserve(
        Tasting $tasting,
        int $slotId,
        Request $request,
        TastingSlotRepository $slotRepository,
    ): Response {
        $slot = $slotRepository->find($slotId);

        if (!$slot || $slot->getTasting() !== $tasting || !$slot->isAvailable() || $slot->isPast()) {
            throw $this->createNotFoundException('Ce créneau n\'est pas disponible.');
        }

        $reservation = new Reservation();
        $this->reservationService->prefillFromUser($reservation, $this->getUser());

        $form = $this->createForm(ReservationType::class, $reservation, [
            'min_participants' => $tasting->getMinParticipants(),
            'max_participants' => min($tasting->getMaxParticipants(), $slot->getRemainingSpots()),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $error = $this->reservationService->createReservation($reservation, $slot);

            if ($error) {
                $this->addFlash('error', $error);

                return $this->redirectToRoute('app_tasting_show', ['slug' => $tasting->getSlug()]);
            }

            $this->addFlash('success', sprintf(
                'Votre réservation %s a bien été enregistrée. Vous recevrez une confirmation par email.',
                $reservation->getReference(),
            ));

            return $this->redirectToRoute('app_tasting_reservation_confirmation', [
                'reference' => $reservation->getReference(),
            ]);
        }

        return $this->render('tasting/reserve.html.twig', [
            'tasting' => $tasting,
            'slot' => $slot,
            'form' => $form,
        ]);
    }

    #[Route('/reservation/{reference}', name: 'app_tasting_reservation_confirmation', priority: 2)]
    public function confirmation(
        string $reference,
        ReservationRepository $reservationRepository,
    ): Response {
        $reservation = $reservationRepository->findByReference($reference);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable.');
        }

        return $this->render('tasting/confirmation.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
