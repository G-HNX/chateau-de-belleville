<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

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

    public function getCartCount(): int
    {
        $cart = $this->cartService->getCart($this->security->getUser());

        return $cart ? $cart->getItemsCount() : 0;
    }
}
