<?php

declare(strict_types=1);

namespace App\Entity\Booking;

use App\Entity\User\User;
use App\Enum\ReservationStatus;
use App\Repository\Booking\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Reservation de degustation.
 */
#[ORM\Entity(repositoryClass: ReservationRepository::class)]
#[ORM\Table(name: 'reservation')]
#[ORM\Index(columns: ['reference'], name: 'idx_reservation_reference')]
#[ORM\Index(columns: ['status'], name: 'idx_reservation_status')]
#[ORM\HasLifecycleCallbacks]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Reference unique (ex: RES-20260128-A7K9).
     */
    #[ORM\Column(length: 20, unique: true)]
    private ?string $reference = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: TastingSlot::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TastingSlot $slot = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ReservationStatus::class)]
    private ReservationStatus $status = ReservationStatus::PENDING;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prenom est obligatoire')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Le telephone est obligatoire')]
    private ?string $phone = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Range(min: 1, max: 20)]
    private int $numberOfParticipants = 2;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $confirmedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSlot(): ?TastingSlot
    {
        return $this->slot;
    }

    public function setSlot(?TastingSlot $slot): static
    {
        $this->slot = $slot;

        return $this;
    }

    public function getStatus(): ReservationStatus
    {
        return $this->status;
    }

    public function setStatus(ReservationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getNumberOfParticipants(): int
    {
        return $this->numberOfParticipants;
    }

    public function setNumberOfParticipants(int $numberOfParticipants): static
    {
        $this->numberOfParticipants = $numberOfParticipants;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function getAdminNotes(): ?string
    {
        return $this->adminNotes;
    }

    public function setAdminNotes(?string $adminNotes): static
    {
        $this->adminNotes = $adminNotes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getConfirmedAt(): ?\DateTimeInterface
    {
        return $this->confirmedAt;
    }

    /**
     * Verifie si la reservation est active.
     */
    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Confirme la reservation.
     */
    public function confirm(): void
    {
        $this->status = ReservationStatus::CONFIRMED;
        $this->confirmedAt = new \DateTime();
    }

    /**
     * Annule la reservation.
     */
    public function cancel(): void
    {
        $this->status = ReservationStatus::CANCELLED;
    }

    /**
     * Calcule le prix total de la reservation.
     */
    public function getTotalPrice(): float
    {
        return $this->slot->getTasting()->getPrice() * $this->numberOfParticipants;
    }

    /**
     * Retourne le prix total formate.
     */
    public function getFormattedTotalPrice(): string
    {
        $total = $this->getTotalPrice();

        return $total > 0
            ? number_format($total, 2, ',', ' ') . ' EUR'
            : 'Gratuit';
    }

    #[ORM\PrePersist]
    public function generateReference(): void
    {
        if (!$this->reference) {
            $this->reference = sprintf(
                'RES-%s-%s',
                date('Ymd'),
                strtoupper(bin2hex(random_bytes(2)))
            );
        }
        $this->createdAt = new \DateTime();
    }
}
