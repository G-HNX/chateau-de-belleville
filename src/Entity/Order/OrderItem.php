<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\Catalog\Wine;
use App\Repository\Order\OrderItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Article dans une commande.
 *
 * Contient un snapshot des informations du vin au moment de l'achat.
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_item')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Order::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    /**
     * Reference au vin (peut etre null si le vin est supprime).
     */
    #[ORM\ManyToOne(targetEntity: Wine::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Wine $wine = null;

    /**
     * Snapshot du nom du vin au moment de l'achat.
     */
    #[ORM\Column(length: 255)]
    private ?string $wineName = null;

    /**
     * Snapshot du millesime au moment de l'achat.
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $wineVintage = null;

    /**
     * Prix unitaire en centimes au moment de l'achat.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $unitPriceInCents = 0;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private int $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getWine(): ?Wine
    {
        return $this->wine;
    }

    public function setWine(?Wine $wine): static
    {
        $this->wine = $wine;

        return $this;
    }

    public function getWineName(): ?string
    {
        return $this->wineName;
    }

    public function setWineName(string $wineName): static
    {
        $this->wineName = $wineName;

        return $this;
    }

    public function getWineVintage(): ?int
    {
        return $this->wineVintage;
    }

    public function setWineVintage(?int $wineVintage): static
    {
        $this->wineVintage = $wineVintage;

        return $this;
    }

    public function getUnitPriceInCents(): int
    {
        return $this->unitPriceInCents;
    }

    public function setUnitPriceInCents(int $unitPriceInCents): static
    {
        $this->unitPriceInCents = $unitPriceInCents;

        return $this;
    }

    public function getUnitPrice(): float
    {
        return $this->unitPriceInCents / 100;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }

    /**
     * Calcule le total de la ligne en centimes.
     */
    public function getTotalInCents(): int
    {
        return $this->unitPriceInCents * $this->quantity;
    }

    /**
     * Calcule le total de la ligne en euros.
     */
    public function getTotal(): float
    {
        return $this->getTotalInCents() / 100;
    }

    /**
     * Retourne le total formate.
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->getTotal(), 2, ',', ' ') . ' EUR';
    }

    /**
     * Cree un OrderItem a partir d'un vin.
     */
    public static function createFromWine(Wine $wine, int $quantity = 1): self
    {
        $item = new self();
        $item->wine = $wine;
        $item->wineName = $wine->getName();
        $item->wineVintage = $wine->getVintage();
        $item->unitPriceInCents = $wine->getPriceInCents();
        $item->quantity = $quantity;

        return $item;
    }

    /**
     * Cree un OrderItem a partir d'un CartItem.
     */
    public static function createFromCartItem(CartItem $cartItem): self
    {
        return self::createFromWine($cartItem->getWine(), $cartItem->getQuantity());
    }
}
