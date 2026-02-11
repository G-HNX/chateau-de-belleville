<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Order\Cart;
use App\Entity\Order\CartItem;
use App\Entity\User\User;
use App\Repository\Order\CartRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class CartService
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    public function getCart(?User $user): ?Cart
    {
        if ($user) {
            return $this->cartRepository->findByUser($user);
        }

        $sessionId = $this->requestStack->getSession()->getId();

        return $this->cartRepository->findBySessionId($sessionId);
    }

    public function getOrCreateCart(?User $user): Cart
    {
        $cart = $this->getCart($user);

        if ($cart) {
            return $cart;
        }

        $cart = new Cart();

        if ($user) {
            $cart->setUser($user);
        } else {
            $cart->setSessionId($this->requestStack->getSession()->getId());
        }

        $this->em->persist($cart);

        return $cart;
    }

    /**
     * Ajoute un vin au panier. Retourne null si OK, ou un message d'erreur.
     */
    public function addWine(?User $user, Wine $wine, int $quantity = 1): ?string
    {
        if (!$wine->isAvailable()) {
            return 'Ce vin n\'est pas disponible.';
        }

        $quantity = max(1, $quantity);

        if (!$wine->hasEnoughStock($quantity)) {
            return 'Stock insuffisant.';
        }

        $cart = $this->getOrCreateCart($user);

        $existingItem = null;
        foreach ($cart->getItems() as $item) {
            if ($item->getWine()->getId() === $wine->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $newQuantity = $existingItem->getQuantity() + $quantity;
            if (!$wine->hasEnoughStock($newQuantity)) {
                return 'Stock insuffisant pour cette quantite.';
            }
            $existingItem->setQuantity($newQuantity);
        } else {
            $cartItem = new CartItem();
            $cartItem->setWine($wine);
            $cartItem->setQuantity($quantity);
            $cart->addItem($cartItem);
        }

        $this->em->flush();

        return null;
    }

    /**
     * Met a jour la quantite d'un article. Retourne null si OK, ou un message d'erreur.
     */
    public function updateItemQuantity(CartItem $cartItem, int $quantity): ?string
    {
        $quantity = max(1, $quantity);

        if (!$cartItem->getWine()->hasEnoughStock($quantity)) {
            return 'Stock insuffisant.';
        }

        $cartItem->setQuantity($quantity);
        $this->em->flush();

        return null;
    }

    public function removeItem(CartItem $cartItem): void
    {
        $cart = $cartItem->getCart();
        $cart->removeItem($cartItem);
        $this->em->flush();
    }

    public function clearCart(?User $user): void
    {
        $cart = $this->getCart($user);

        if ($cart) {
            $cart->clear();
            $this->em->flush();
        }
    }
}
