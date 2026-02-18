<?php

declare(strict_types=1);

namespace App\Entity\Domain;

use App\Enum\DomainSection;
use App\Repository\Domain\DomainPhotoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: DomainPhotoRepository::class)]
#[ORM\Table(name: 'domain_photo')]
#[ORM\Index(columns: ['section', 'is_active', 'position'], name: 'idx_domain_photo_section')]
class DomainPhoto
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: DomainSection::class)]
    #[Assert\NotNull]
    private ?DomainSection $section = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $filename = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $caption = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private int $position = 0;

    #[ORM\Column]
    private bool $isActive = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSection(): ?DomainSection
    {
        return $this->section;
    }

    public function setSection(DomainSection $section): static
    {
        $this->section = $section;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPath(): string
    {
        return '/uploads/domain/' . $this->filename;
    }

    public function getCaption(): ?string
    {
        return $this->caption;
    }

    public function setCaption(?string $caption): static
    {
        $this->caption = $caption;

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

    public function __toString(): string
    {
        return ($this->section?->label() ?? '') . ' #' . ($this->id ?? 'new');
    }
}
