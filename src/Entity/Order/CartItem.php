<?php

declare(strict_types=1);

namespace App\Entity\Order;

use App\Entity\Catalog\Wine;
use App\Repository\Order\CartItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Article dans un panier.
 */
#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_item')]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Cart $cart = null;

    #[ORM\ManyToOne(targetEntity: Wine::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Wine $wine = null;

    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\Positive]
    private int $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

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

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }

    public function incrementQuantity(int $amount = 1): void
    {
        $this->quantity += $amount;
    }

    public function decrementQuantity(int $amount = 1): void
    {
        $this->quantity = max(1, $this->quantity - $amount);
    }

    /**
     * Calcule le total de la ligne en centimes.
     */
    public function getTotalInCents(): int
    {
        return $this->wine->getPriceInCents() * $this->quantity;
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
     * Retourne le prix unitaire.
     */
    public function getUnitPrice(): float
    {
        return $this->wine->getPrice();
    }
}
