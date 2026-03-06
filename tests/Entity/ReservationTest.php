<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Booking\Reservation;
use App\Enum\ReservationStatus;
use PHPUnit\Framework\TestCase;

class ReservationTest extends TestCase
{
    public function testGetTotalPrice(): void
    {
        $reservation = new Reservation();
        $reservation->setPricePerPersonInCents(2500);
        $reservation->setNumberOfParticipants(3);

        $this->assertSame(75.0, $reservation->getTotalPrice());
    }

    public function testGetFormattedTotalPrice(): void
    {
        $reservation = new Reservation();
        $reservation->setPricePerPersonInCents(3500);
        $reservation->setNumberOfParticipants(2);

        $this->assertStringContainsString('EUR', $reservation->getFormattedTotalPrice());
        $this->assertStringContainsString('70', $reservation->getFormattedTotalPrice());
    }

    public function testGetFormattedTotalPriceFreeWhenZero(): void
    {
        $reservation = new Reservation();
        $reservation->setPricePerPersonInCents(0);
        $reservation->setNumberOfParticipants(2);

        $this->assertSame('Gratuit', $reservation->getFormattedTotalPrice());
    }

    public function testConfirmSetsStatusAndDate(): void
    {
        $reservation = new Reservation();
        $this->assertSame(ReservationStatus::PENDING, $reservation->getStatus());
        $this->assertNull($reservation->getConfirmedAt());

        $reservation->confirm();

        $this->assertSame(ReservationStatus::CONFIRMED, $reservation->getStatus());
        $this->assertNotNull($reservation->getConfirmedAt());
    }

    public function testCancelSetsStatus(): void
    {
        $reservation = new Reservation();
        $reservation->confirm();

        $reservation->cancel();

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->getStatus());
    }

    public function testIsActiveForConfirmedStatus(): void
    {
        $reservation = new Reservation();
        $reservation->confirm();

        $this->assertTrue($reservation->isActive());
    }

    public function testIsActiveForCancelledStatus(): void
    {
        $reservation = new Reservation();
        $reservation->cancel();

        $this->assertFalse($reservation->isActive());
    }

    public function testGenerateReferenceStartsWithRES(): void
    {
        $reservation = new Reservation();
        $reservation->generateReference();

        $this->assertNotNull($reservation->getReference());
        $this->assertStringStartsWith('RES-', $reservation->getReference());
    }

    public function testGenerateReferenceIsIdempotent(): void
    {
        $reservation = new Reservation();
        $reservation->generateReference();
        $first = $reservation->getReference();

        $reservation->generateReference();

        $this->assertSame($first, $reservation->getReference());
    }

    public function testGetFullName(): void
    {
        $reservation = new Reservation();
        $reservation->setFirstName('Marie');
        $reservation->setLastName('Dupont');

        $this->assertSame('Marie Dupont', $reservation->getFullName());
    }

    public function testCreatedAtSetInConstructor(): void
    {
        $reservation = new Reservation();

        $this->assertNotNull($reservation->getCreatedAt());
    }
}
