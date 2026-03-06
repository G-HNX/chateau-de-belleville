<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Booking\Reservation;
use App\Entity\Booking\Tasting;
use App\Entity\Booking\TastingSlot;
use App\Service\EmailService;
use App\Service\ReservationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class ReservationServiceTest extends TestCase
{
    private function makeReservation(int $participants = 2): Reservation
    {
        $r = new Reservation();
        $r->setFirstName('Marie');
        $r->setLastName('Curie');
        $r->setEmail('marie@example.com');
        $r->setPhone('0600000000');
        $r->setNumberOfParticipants($participants);

        return $r;
    }

    private function makeSlot(int $availableSpots, int $priceInCents = 3500): TastingSlot
    {
        $tasting = $this->createStub(Tasting::class);
        $tasting->method('getPriceInCents')->willReturn($priceInCents);

        $slot = $this->createStub(TastingSlot::class);
        $slot->method('getAvailableSpots')->willReturn($availableSpots);
        $slot->method('getTasting')->willReturn($tasting);

        return $slot;
    }

    private function makeQueryReturning(int $bookedSpots): \Doctrine\ORM\Query
    {
        $query = $this->createStub(\Doctrine\ORM\Query::class);
        $query->method('setParameter')->willReturnSelf();
        $query->method('getSingleScalarResult')->willReturn($bookedSpots);

        return $query;
    }

    private function makeEmStub(int $bookedSpots = 0): EntityManagerInterface
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());
        $em->method('createQuery')->willReturn($this->makeQueryReturning($bookedSpots));

        return $em;
    }

    // ------- createReservation -------

    public function testCreateReservationSlotFullReturnsError(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());
        $em->method('createQuery')->willReturn($this->makeQueryReturning(10));
        $em->expects($this->never())->method('persist');

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->never())->method('sendReservationConfirmation');

        $service = new ReservationService($em, $emailService);
        // Slot has 10 available spots, 10 already booked, requesting 4 → full
        $error = $service->createReservation($this->makeReservation(4), $this->makeSlot(10));

        $this->assertSame('Il n\'y a pas assez de places disponibles pour ce créneau.', $error);
    }

    public function testCreateReservationSavesPriceSnapshot(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());
        $em->method('createQuery')->willReturn($this->makeQueryReturning(0));
        $em->expects($this->once())->method('persist');

        $emailService = $this->createStub(EmailService::class);
        $service = new ReservationService($em, $emailService);

        $reservation = $this->makeReservation(2);
        $error = $service->createReservation($reservation, $this->makeSlot(10, 4200));

        $this->assertNull($error);
        $this->assertSame(4200, $reservation->getPricePerPersonInCents());
    }

    public function testCreateReservationSetsSlotOnReservation(): void
    {
        $em = $this->makeEmStub();
        $em->method('persist');

        $emailService = $this->createStub(EmailService::class);
        $service = new ReservationService($em, $emailService);

        $reservation = $this->makeReservation(1);
        $slot = $this->makeSlot(10, 2500);

        $service->createReservation($reservation, $slot);

        $this->assertSame($slot, $reservation->getSlot());
    }

    public function testCreateReservationSendsEmailOnSuccess(): void
    {
        $em = $this->makeEmStub();
        $em->method('persist');

        $emailService = $this->createMock(EmailService::class);
        $emailService->expects($this->once())->method('sendReservationConfirmation');

        $service = new ReservationService($em, $emailService);
        $service->createReservation($this->makeReservation(2), $this->makeSlot(10));
    }

    public function testCreateReservationEmailTransportExceptionNotPropagated(): void
    {
        $em = $this->makeEmStub();
        $em->method('persist');

        $emailService = $this->createStub(EmailService::class);
        $emailService->method('sendReservationConfirmation')
            ->willThrowException($this->createStub(TransportExceptionInterface::class));

        $service = new ReservationService($em, $emailService);

        // Ne doit pas propager l'exception de transport
        $error = $service->createReservation($this->makeReservation(2), $this->makeSlot(10));

        $this->assertNull($error);
    }
}
