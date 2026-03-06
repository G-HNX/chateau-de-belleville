<?php

declare(strict_types=1);

namespace App\Entity\User;

use App\Entity\Catalog\Wine;
use App\Entity\Customer\Address;
use App\Entity\Order\Cart;
use App\Entity\Order\Order;
use App\Repository\User\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe deja avec cet email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire')]
    #[Assert\Email(message: 'L\'email n\'est pas valide')]
    private ?string $email = null;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prenom est obligatoire')]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column]
    private bool $newsletterOptIn = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    /** @var Collection<int, Address> */
    #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $addresses;

    /** @var Collection<int, Order> */
    #[ORM\OneToMany(targetEntity: Order::class, mappedBy: 'customer')]
    private Collection $orders;

    #[ORM\OneToOne(targetEntity: Cart::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Cart $cart = null;

    /** @var Collection<int, Wine> */
    #[ORM\ManyToMany(targetEntity: Wine::class)]
    #[ORM\JoinTable(name: 'user_favorite_wines')]
    private Collection $favoriteWines;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->favoriteWines = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clear temporary sensitive data if any
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isNewsletterOptIn(): bool
    {
        return $this->newsletterOptIn;
    }

    public function setNewsletterOptIn(bool $newsletterOptIn): static
    {
        $this->newsletterOptIn = $newsletterOptIn;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    /** @return Collection<int, Address> */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): static
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, Order> */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        if ($cart === null && $this->cart !== null) {
            $this->cart->setUser(null);
        }

        if ($cart !== null && $cart->getUser() !== $this) {
            $cart->setUser($this);
        }

        $this->cart = $cart;

        return $this;
    }

    public function getDefaultShippingAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->isDefaultShipping()) {
                return $address;
            }
        }

        return $this->addresses->first() ?: null;
    }

    public function getDefaultBillingAddress(): ?Address
    {
        foreach ($this->addresses as $address) {
            if ($address->isDefaultBilling()) {
                return $address;
            }
        }

        return $this->getDefaultShippingAddress();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
    }

    /** @return Collection<int, Wine> */
    public function getFavoriteWines(): Collection
    {
        return $this->favoriteWines;
    }

    public function addFavoriteWine(Wine $wine): static
    {
        if (!$this->favoriteWines->contains($wine)) {
            $this->favoriteWines->add($wine);
        }

        return $this;
    }

    public function removeFavoriteWine(Wine $wine): static
    {
        $this->favoriteWines->removeElement($wine);

        return $this;
    }

    public function isFavoriteWine(Wine $wine): bool
    {
        return $this->favoriteWines->contains($wine);
    }

    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles, true);
    }

    public function __toString(): string
    {
        return $this->getFullName() ?: $this->email ?? '';
    }
}
