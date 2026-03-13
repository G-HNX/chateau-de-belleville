<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extension Twig exposant le nombre d'articles dans le panier.
 * Fournit la fonction `cart_count()` utilisée dans la navbar pour
 * afficher le badge du panier en temps réel.
 */
class CartExtension extends AbstractExtension
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly Security $security,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', $this->getCartCount(...)),
        ];
    }

    /** Retourne le nombre total d'articles dans le panier de l'utilisateur courant. */
    public function getCartCount(): int
    {
        $cart = $this->cartService->getCart($this->security->getUser());

        return $cart ? $cart->getItemsCount() : 0;
    }
}
