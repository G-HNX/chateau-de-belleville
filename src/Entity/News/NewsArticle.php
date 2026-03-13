<?php

declare(strict_types=1);

namespace App\Entity\News;

use App\Repository\News\NewsArticleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Article d'actualite du domaine.
 *
 * Permet de publier des nouvelles sur le site (evenements, vendanges, nouveaux vins, etc.).
 * Un article peut etre en brouillon ou publie, avec une date de publication automatique.
 */
#[ORM\Entity(repositoryClass: NewsArticleRepository::class)]
#[ORM\Table(name: 'news_article')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['slug'], name: 'idx_news_article_slug')]
#[ORM\Index(columns: ['is_published', 'published_at'], name: 'idx_news_article_published')]
class NewsArticle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $title = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    /** Resume court affiche dans la liste des articles. */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerpt = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    private ?string $content = null;

    /** Image de couverture de l'article (nom de fichier). */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(pattern: '/^[\w\-]+\.(jpe?g|png|webp|gif)$/i', message: 'Seules les images (jpg, png, webp, gif) sont autorisées.')]
    private ?string $coverImage = null;

    #[ORM\Column]
    private bool $isPublished = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    /** Initialise les dates, genere le slug et positionne la date de publication si necessaire. */
    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();

        if (!$this->slug && $this->title) {
            $slugger = new AsciiSlugger('fr');
            $this->slug = strtolower($slugger->slug($this->title)->toString());
        }

        if ($this->isPublished && !$this->publishedAt) {
            $this->publishedAt = new \DateTime();
        }
    }

    /** Met a jour la date de modification et positionne la date de publication si necessaire. */
    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();

        if ($this->isPublished && !$this->publishedAt) {
            $this->publishedAt = new \DateTime();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

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

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function setExcerpt(?string $excerpt): static
    {
        $this->excerpt = $excerpt;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): static
    {
        $this->coverImage = $coverImage;

        return $this;
    }

    /** Retourne le chemin public vers l'image de couverture. */
    public function getCoverImagePath(): ?string
    {
        return $this->coverImage ? '/uploads/news/' . $this->coverImage : null;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeInterface $publishedAt): static
    {
        $this->publishedAt = $publishedAt;

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

    public function __toString(): string
    {
        return $this->title ?? '';
    }
}
