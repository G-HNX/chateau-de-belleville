<?php

declare(strict_types=1);

namespace App\Entity\Catalog;

use App\Repository\Catalog\WineCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Categorie de vins pour organiser le catalogue.
 * Ex: "Grands Crus", "Cuvees Prestige", "Vins de Table"
 */
#[ORM\Entity(repositoryClass: WineCategoryRepository::class)]
#[ORM\Table(name: 'wine_category')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug existe deja')]
class WineCategory
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

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'smallint')]
    private int $position = 0;

    #[ORM\Column]
    private bool $isActive = true;

    /** @var Collection<int, Wine> */
    #[ORM\OneToMany(targetEntity: Wine::class, mappedBy: 'category')]
    private Collection $wines;

    public function __construct()
    {
        $this->wines = new ArrayCollection();
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

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

    /** @return Collection<int, Wine> */
    public function getWines(): Collection
    {
        return $this->wines;
    }

    public function getActiveWinesCount(): int
    {
        return $this->wines->filter(fn (Wine $w) => $w->isActive())->count();
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
