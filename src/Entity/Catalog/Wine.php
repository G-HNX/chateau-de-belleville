<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Entity\Customer\Review;
use App\Repository\Catalog\WineRepository;
use App\Entity\Catalog\FoodPairing;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entite representant un vin du catalogue.
 *
 * Chaque vin possede des caracteristiques detaillees pour
 * permettre au chatbot IA de faire des recommandations pertinentes.
 */
#[ORM\Entity(repositoryClass: WineRepository::class)]
#[ORM\Table(name: 'wine')]
#[ORM\Index(columns: ['slug'], name: 'idx_wine_slug')]
#[ORM\Index(columns: ['is_active', 'is_featured'], name: 'idx_wine_active_featured')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug existe deja')]
class Wine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du vin est obligatoire')]
    private ?string $name = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    #[Assert\Range(min: 1900, max: 2100)]
    private ?int $vintage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $shortDescription = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Prix TTC en centimes (850 = 8,50 EUR).
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive(message: 'Le prix doit etre positif')]
    private int $priceInCents = 0;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero]
    private int $stock = 0;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 1, nullable: true)]
    private ?string $alcoholDegree = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $servingTemperature = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $agingPotential = null;

    /** @var Collection<int, FoodPairing> */
    #[ORM\ManyToMany(targetEntity: FoodPairing::class, inversedBy: 'wines')]
    #[ORM\JoinTable(name: 'wine_food_pairing')]
    private Collection $foodPairings;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tastingNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $terroir = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $volumeCl = 75;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    // Relations

    #[ORM\ManyToOne(targetEntity: WineCategory::class, inversedBy: 'wines')]
    #[ORM\JoinColumn(nullable: true)]
    private ?WineCategory $category = null;

    #[ORM\ManyToOne(targetEntity: Appellation::class, inversedBy: 'wines')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Appellation $appellation = null;

    /** @var Collection<int, GrapeVariety> */
    #[ORM\ManyToMany(targetEntity: GrapeVariety::class, inversedBy: 'wines')]
    #[ORM\JoinTable(name: 'wine_grape_variety')]
    private Collection $grapeVarieties;

    /** @var Collection<int, WineImage> */
    #[ORM\OneToMany(targetEntity: WineImage::class, mappedBy: 'wine', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $images;

    /** @var Collection<int, Review> */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'wine')]
    private Collection $reviews;

    public function __construct()
    {
        $this->grapeVarieties = new ArrayCollection();
        $this->foodPairings = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    // Getters & Setters

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

    public function getVintage(): ?int
    {
        return $this->vintage;
    }

    public function setVintage(?int $vintage): static
    {
        $this->vintage = $vintage;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(?string $shortDescription): static
    {
        $this->shortDescription = $shortDescription;

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

    public function getFormattedPrice(): string
    {
        return number_format($this->getPrice(), 2, ',', ' ') . ' EUR';
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function hasEnoughStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }

    public function decrementStock(int $quantity = 1): static
    {
        $this->stock = max(0, $this->stock - $quantity);

        return $this;
    }

    public function incrementStock(int $quantity = 1): static
    {
        $this->stock += $quantity;

        return $this;
    }

    public function getAlcoholDegree(): ?string
    {
        return $this->alcoholDegree;
    }

    public function setAlcoholDegree(?string $alcoholDegree): static
    {
        $this->alcoholDegree = $alcoholDegree;

        return $this;
    }

    public function getServingTemperature(): ?string
    {
        return $this->servingTemperature;
    }

    public function setServingTemperature(?string $servingTemperature): static
    {
        $this->servingTemperature = $servingTemperature;

        return $this;
    }

    public function getAgingPotential(): ?string
    {
        return $this->agingPotential;
    }

    public function setAgingPotential(?string $agingPotential): static
    {
        $this->agingPotential = $agingPotential;

        return $this;
    }

    /** @return Collection<int, FoodPairing> */
    public function getFoodPairings(): Collection
    {
        return $this->foodPairings;
    }

    public function addFoodPairing(FoodPairing $foodPairing): static
    {
        if (!$this->foodPairings->contains($foodPairing)) {
            $this->foodPairings->add($foodPairing);
        }

        return $this;
    }

    public function removeFoodPairing(FoodPairing $foodPairing): static
    {
        $this->foodPairings->removeElement($foodPairing);

        return $this;
    }

    public function getFoodPairingsAsString(): string
    {
        return implode(', ', $this->foodPairings->map(
            fn (FoodPairing $fp) => $fp->getName()
        )->toArray());
    }

    public function getTastingNotes(): ?string
    {
        return $this->tastingNotes;
    }

    public function setTastingNotes(?string $tastingNotes): static
    {
        $this->tastingNotes = $tastingNotes;

        return $this;
    }

    public function getTerroir(): ?string
    {
        return $this->terroir;
    }

    public function setTerroir(?string $terroir): static
    {
        $this->terroir = $terroir;

        return $this;
    }

    public function getVolumeCl(): int
    {
        return $this->volumeCl;
    }

    public function setVolumeCl(int $volumeCl): static
    {
        $this->volumeCl = $volumeCl;

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

    public function isFeatured(): bool
    {
        return $this->isFeatured;
    }

    public function setIsFeatured(bool $isFeatured): static
    {
        $this->isFeatured = $isFeatured;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getCategory(): ?WineCategory
    {
        return $this->category;
    }

    public function setCategory(?WineCategory $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getAppellation(): ?Appellation
    {
        return $this->appellation;
    }

    public function setAppellation(?Appellation $appellation): static
    {
        $this->appellation = $appellation;

        return $this;
    }

    /** @return Collection<int, GrapeVariety> */
    public function getGrapeVarieties(): Collection
    {
        return $this->grapeVarieties;
    }

    public function addGrapeVariety(GrapeVariety $grapeVariety): static
    {
        if (!$this->grapeVarieties->contains($grapeVariety)) {
            $this->grapeVarieties->add($grapeVariety);
        }

        return $this;
    }

    public function removeGrapeVariety(GrapeVariety $grapeVariety): static
    {
        $this->grapeVarieties->removeElement($grapeVariety);

        return $this;
    }

    public function getGrapeVarietiesAsString(): string
    {
        return implode(', ', $this->grapeVarieties->map(
            fn (GrapeVariety $gv) => $gv->getName()
        )->toArray());
    }

    /** @return Collection<int, WineImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(WineImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setWine($this);
        }

        return $this;
    }

    public function removeImage(WineImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getWine() === $this) {
                $image->setWine(null);
            }
        }

        return $this;
    }

    public function getMainImage(): ?WineImage
    {
        foreach ($this->images as $image) {
            if ($image->isMain()) {
                return $image;
            }
        }

        return $this->images->first() ?: null;
    }

    /** @return Collection<int, Review> */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getAverageRating(): ?float
    {
        $approvedReviews = $this->reviews->filter(fn (Review $r) => $r->isApproved());

        if ($approvedReviews->isEmpty()) {
            return null;
        }

        $total = 0;
        foreach ($approvedReviews as $review) {
            $total += $review->getRating();
        }

        return round($total / $approvedReviews->count(), 1);
    }

    public function isAvailable(): bool
    {
        return $this->isActive && $this->stock > 0;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!$this->slug && $this->name) {
            $slugger = new AsciiSlugger('fr');
            $this->slug = strtolower($slugger->slug($this->name)->toString());
        }
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }
}
