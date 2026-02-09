<?php

declare(strict_types=1);

namespace App\Entity\Booking;

use App\Repository\Booking\TastingSlotRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Creneau horaire disponible pour une degustation.
 */
#[ORM\Entity(repositoryClass: TastingSlotRepository::class)]
#[ORM\Table(name: 'tasting_slot')]
#[ORM\Index(columns: ['date', 'start_time'], name: 'idx_slot_datetime')]
class TastingSlot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Tasting::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tasting $tasting = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private int $availableSpots = 10;

    #[ORM\Column]
    private bool $isAvailable = true;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'slot')]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTasting(): ?Tasting
    {
        return $this->tasting;
    }

    public function setTasting(?Tasting $tasting): static
    {
        $this->tasting = $tasting;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getAvailableSpots(): int
    {
        return $this->availableSpots;
    }

    public function setAvailableSpots(int $availableSpots): static
    {
        $this->availableSpots = $availableSpots;

        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    /**
     * Calcule le nombre de places reservees (reservations actives).
     */
    public function getBookedSpots(): int
    {
        $booked = 0;
        foreach ($this->reservations as $reservation) {
            if ($reservation->isActive()) {
                $booked += $reservation->getNumberOfParticipants();
            }
        }

        return $booked;
    }

    /**
     * Calcule le nombre de places restantes.
     */
    public function getRemainingSpots(): int
    {
        return max(0, $this->availableSpots - $this->getBookedSpots());
    }

    /**
     * Verifie si le creneau peut accueillir un nombre de participants.
     */
    public function canAccommodate(int $participants): bool
    {
        return $this->isAvailable && $this->getRemainingSpots() >= $participants;
    }

    /**
     * Verifie si le creneau est dans le passe.
     */
    public function isPast(): bool
    {
        $now = new \DateTime();
        $slotDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i',
            $this->date->format('Y-m-d') . ' ' . $this->startTime->format('H:i')
        );

        return $slotDateTime < $now;
    }

    /**
     * Retourne la date et l'heure formatees.
     */
    public function getFormattedDateTime(): string
    {
        return $this->date->format('d/m/Y') . ' a ' . $this->startTime->format('H:i');
    }

    /**
     * Retourne l'heure de fin estimee.
     */
    public function getEndTime(): \DateTimeInterface
    {
        $endTime = clone $this->startTime;
        $endTime->modify('+' . $this->tasting->getDurationMinutes() . ' minutes');

        return $endTime;
    }

    public function __toString(): string
    {
        return $this->getFormattedDateTime();
    }
}
