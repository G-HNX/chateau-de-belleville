<?php

declare(strict_types=1);

namespace App\Entity\Booking;

use App\Repository\Booking\TastingRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Type de degustation proposee au domaine.
 * Ex: "Decouverte", "Prestige", "Exception"
 */
#[ORM\Entity(repositoryClass: TastingRepository::class)]
#[ORM\Table(name: 'tasting')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug existe deja')]
class Tasting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $name = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Prix par personne en centimes (0 = gratuit).
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero]
    private int $priceInCents = 0;

    /**
     * Duree de la degustation en minutes.
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private int $durationMinutes = 60;

    /**
     * Nombre maximum de participants par creneau.
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private int $maxParticipants = 10;

    /**
     * Nombre minimum de participants pour confirmer.
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private int $minParticipants = 2;

    #[ORM\Column]
    private bool $isActive = true;

    /**
     * Elements inclus dans la formule.
     *
     * @var array<int, string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $includedItems = null;

    /** @var Collection<int, TastingSlot> */
    #[ORM\OneToMany(targetEntity: TastingSlot::class, mappedBy: 'tasting', cascade: ['persist', 'remove'])]
    private Collection $slots;

    public function __construct()
    {
        $this->slots = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriceInCents(): int
    {
        return $this->priceInCents;
    }

    public function setPriceInCents(int $priceInCents): static
    {
        $this->priceInCents = $priceInCents;

        return $this;
    }

    public function getPrice(): float
    {
        return $this->priceInCents / 100;
    }

    public function setPrice(float $price): static
    {
        $this->priceInCents = (int) round($price * 100);

        return $this;
    }

    public function isFree(): bool
    {
        return $this->priceInCents === 0;
    }

    public function getFormattedPrice(): string
    {
        return $this->isFree()
            ? 'Gratuit'
            : number_format($this->getPrice(), 2, ',', ' ') . ' EUR / pers.';
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function setDurationMinutes(int $durationMinutes): static
    {
        $this->durationMinutes = $durationMinutes;

        return $this;
    }

    public function getFormattedDuration(): string
    {
        $hours = intdiv($this->durationMinutes, 60);
        $minutes = $this->durationMinutes % 60;

        if ($hours === 0) {
            return $minutes . ' min';
        }

        if ($minutes === 0) {
            return $hours . 'h';
        }

        return $hours . 'h' . $minutes;
    }

    public function getMaxParticipants(): int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(int $maxParticipants): static
    {
        $this->maxParticipants = $maxParticipants;

        return $this;
    }

    public function getMinParticipants(): int
    {
        return $this->minParticipants;
    }

    public function setMinParticipants(int $minParticipants): static
    {
        $this->minParticipants = $minParticipants;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /** @return array<int, string>|null */
    public function getIncludedItems(): ?array
    {
        return $this->includedItems;
    }

    /** @param array<int, string>|null $includedItems */
    public function setIncludedItems(?array $includedItems): static
    {
        $this->includedItems = $includedItems;

        return $this;
    }

    /** @return Collection<int, TastingSlot> */
    public function getSlots(): Collection
    {
        return $this->slots;
    }

    public function addSlot(TastingSlot $slot): static
    {
        if (!$this->slots->contains($slot)) {
            $this->slots->add($slot);
            $slot->setTasting($this);
        }

        return $this;
    }

    public function removeSlot(TastingSlot $slot): static
    {
        if ($this->slots->removeElement($slot)) {
            if ($slot->getTasting() === $this) {
                $slot->setTasting(null);
            }
        }

        return $this;
    }

    #[ORM\PrePersist]
    public function generateSlug(): void
    {
        if (!$this->slug && $this->name) {
            $slugger = new AsciiSlugger('fr');
            $this->slug = strtolower($slugger->slug($this->name)->toString());
        }
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
