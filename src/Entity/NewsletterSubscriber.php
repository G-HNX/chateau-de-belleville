<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
#[ORM\Table(name: 'newsletter_subscriber')]
#[ORM\HasLifecycleCallbacks]
class NewsletterSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column]
    private \DateTimeImmutable $subscribedAt;

    #[ORM\Column(length: 64, unique: true)]
    private string $unsubscribeToken = '';

    public function __construct()
    {
        $this->subscribedAt = new \DateTimeImmutable();
        $this->unsubscribeToken = bin2hex(random_bytes(32));
    }

    #[ORM\PrePersist]
    public function initDefaults(): void
    {
        if ($this->unsubscribeToken === '') {
            $this->unsubscribeToken = bin2hex(random_bytes(32));
        }
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getSubscribedAt(): \DateTimeImmutable
    {
        return $this->subscribedAt;
    }

    public function getUnsubscribeToken(): string
    {
        return $this->unsubscribeToken;
    }
}
