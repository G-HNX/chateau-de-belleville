<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\User\User;
use App\Enum\OrderStatus;
use App\Repository\Order\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Commande client.
 *
 * Une commande contient un snapshot des informations au moment de l'achat
 * pour garantir l'integrite meme si les produits ou prix changent apres.
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\Index(columns: ['reference'], name: 'idx_order_reference')]
#[ORM\Index(columns: ['status'], name: 'idx_order_status')]
#[ORM\Index(columns: ['created_at'], name: 'idx_order_date')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Reference unique lisible (ex: CB-20260128-A7K9).
     */
    #[ORM\Column(length: 20, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::PENDING;

    /**
     * Client ayant passe la commande (nullable pour les commandes invite).
     */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $customer = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $customerEmail = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $customerFirstName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $customerLastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $customerPhone = null;

    /**
     * Adresse de facturation (snapshot JSON).
     *
     * @var array<string, string|null>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $billingAddress = [];

    /**
     * Adresse de livraison (snapshot JSON).
     *
     * @var array<string, string|null>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $shippingAddress = [];

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(type: Types::INTEGER)]
    private int $subtotalInCents = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $shippingCostInCents = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $taxAmountInCents = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $totalInCents = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripePaymentIntentId = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $trackingNumber = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $carrier = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $customerNotes = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $paidAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $shippedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $deliveredAt = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
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

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCustomer(): ?User
    {
        return $this->customer;
    }

    public function setCustomer(?User $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getCustomerFirstName(): ?string
    {
        return $this->customerFirstName;
    }

    public function setCustomerFirstName(?string $customerFirstName): static
    {
        $this->customerFirstName = $customerFirstName;

        return $this;
    }

    public function getCustomerLastName(): ?string
    {
        return $this->customerLastName;
    }

    public function setCustomerLastName(?string $customerLastName): static
    {
        $this->customerLastName = $customerLastName;

        return $this;
    }

    public function getCustomerFullName(): string
    {
        return trim($this->customerFirstName . ' ' . $this->customerLastName);
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(?string $customerPhone): static
    {
        $this->customerPhone = $customerPhone;

        return $this;
    }

    /** @return array<string, string|null> */
    public function getBillingAddress(): array
    {
        return $this->billingAddress;
    }

    /** @param array<string, string|null> $billingAddress */
    public function setBillingAddress(array $billingAddress): static
    {
        $this->billingAddress = $billingAddress;

        return $this;
    }

    /** @return array<string, string|null> */
    public function getShippingAddress(): array
    {
        return $this->shippingAddress;
    }

    /** @param array<string, string|null> $shippingAddress */
    public function setShippingAddress(array $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    public function getSubtotalInCents(): int
    {
        return $this->subtotalInCents;
    }

    public function getShippingCostInCents(): int
    {
        return $this->shippingCostInCents;
    }

    public function setShippingCostInCents(int $shippingCostInCents): static
    {
        $this->shippingCostInCents = $shippingCostInCents;

        return $this;
    }

    public function getTaxAmountInCents(): int
    {
        return $this->taxAmountInCents;
    }

    public function getTotalInCents(): int
    {
        return $this->totalInCents;
    }

    public function getSubtotal(): float
    {
        return $this->subtotalInCents / 100;
    }

    public function getShippingCost(): float
    {
        return $this->shippingCostInCents / 100;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmountInCents / 100;
    }

    public function getTotal(): float
    {
        return $this->totalInCents / 100;
    }

    public function getFormattedTotal(): string
    {
        return number_format($this->getTotal(), 2, ',', ' ') . ' EUR';
    }

    /**
     * Recalcule les totaux de la commande.
     */
    public function calculateTotals(): void
    {
        $subtotal = 0;
        foreach ($this->items as $item) {
            $subtotal += $item->getTotalInCents();
        }

        $this->subtotalInCents = $subtotal;

        // Livraison gratuite au-dessus de 150 EUR
        $this->shippingCostInCents = $subtotal >= 15000 ? 0 : 990;

        // TVA 20%
        $this->taxAmountInCents = (int) round(($subtotal + $this->shippingCostInCents) * 0.20);

        $this->totalInCents = $subtotal + $this->shippingCostInCents;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripePaymentIntentId;
    }

    public function setStripePaymentIntentId(?string $stripePaymentIntentId): static
    {
        $this->stripePaymentIntentId = $stripePaymentIntentId;

        return $this;
    }

    public function getTrackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    public function setTrackingNumber(?string $trackingNumber): static
    {
        $this->trackingNumber = $trackingNumber;

        return $this;
    }

    public function getCarrier(): ?string
    {
        return $this->carrier;
    }

    public function setCarrier(?string $carrier): static
    {
        $this->carrier = $carrier;

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

    public function getCustomerNotes(): ?string
    {
        return $this->customerNotes;
    }

    public function setCustomerNotes(?string $customerNotes): static
    {
        $this->customerNotes = $customerNotes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getPaidAt(): ?\DateTimeInterface
    {
        return $this->paidAt;
    }

    public function getShippedAt(): ?\DateTimeInterface
    {
        return $this->shippedAt;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function getItemsCount(): int
    {
        $count = 0;
        foreach ($this->items as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [OrderStatus::PENDING, OrderStatus::PAID], true);
    }

    public function markAsPaid(): void
    {
        $this->status = OrderStatus::PAID;
        $this->paidAt = new \DateTime();
    }

    public function markAsShipped(string $trackingNumber = null, string $carrier = null): void
    {
        $this->status = OrderStatus::SHIPPED;
        $this->shippedAt = new \DateTime();
        $this->trackingNumber = $trackingNumber;
        $this->carrier = $carrier;
    }

    public function markAsDelivered(): void
    {
        $this->status = OrderStatus::DELIVERED;
        $this->deliveredAt = new \DateTime();
    }

    #[ORM\PrePersist]
    public function generateReference(): void
    {
        if (!$this->reference) {
            $this->reference = sprintf(
                'CB-%s-%s',
                date('Ymd'),
                strtoupper(bin2hex(random_bytes(2)))
            );
        }
        $this->createdAt = new \DateTime();
    }

    public function __toString(): string
    {
        return $this->reference ?? '';
    }
}
