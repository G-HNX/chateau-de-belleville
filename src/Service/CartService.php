<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Order\Cart;
use App\Entity\Order\CartItem;
use App\Entity\User\User;
use App\Repository\Order\CartRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion du panier d'achat.
 *
 * Gère la création, la récupération et la modification du panier,
 * aussi bien pour les utilisateurs connectés (via User) que pour
 * les visiteurs anonymes (via l'identifiant de session).
 */
class CartService
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    /**
     * Récupère le panier existant d'un utilisateur connecté ou d'un visiteur anonyme.
     * Retourne null si aucun panier n'existe.
     */
    public function getCart(?User $user): ?Cart
    {
        if ($user) {
            return $this->cartRepository->findByUser($user);
        }

        $sessionId = $this->requestStack->getSession()->getId();

        return $this->cartRepository->findBySessionId($sessionId);
    }

    /**
     * Récupère le panier existant ou en crée un nouveau.
     * Le panier est lié à l'utilisateur connecté, ou à la session pour les visiteurs anonymes.
     */
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
        $error = null;

        $cart = $this->getOrCreateCart($user);

        // Transaction avec verrou pessimiste pour éviter les conditions de concurrence sur le stock
        $this->em->wrapInTransaction(function () use ($cart, $wine, $quantity, &$error): void {
            // Verrou de lecture partagé : recharge le stock depuis la DB
            $freshWine = $this->em->find(Wine::class, $wine->getId(), LockMode::PESSIMISTIC_READ);
            if ($freshWine === null || !$freshWine->isAvailable()) {
                $error = 'Ce vin n\'est pas disponible.';
                return;
            }

            // Recherche si ce vin est déjà présent dans le panier
            $existingItem = null;
            foreach ($cart->getItems() as $item) {
                if ($item->getWine()->getId() === $freshWine->getId()) {
                    $existingItem = $item;
                    break;
                }
            }

            if ($existingItem) {
                // Le vin est déjà dans le panier : on incrémente la quantité
                $newQuantity = $existingItem->getQuantity() + $quantity;
                if (!$freshWine->hasEnoughStock($newQuantity)) {
                    $error = 'Stock insuffisant pour cette quantité.';
                    return;
                }
                $existingItem->setQuantity($newQuantity);
            } else {
                // Nouveau vin : on crée un nouvel article dans le panier
                if (!$freshWine->hasEnoughStock($quantity)) {
                    $error = 'Stock insuffisant.';
                    return;
                }
                $cartItem = new CartItem();
                $cartItem->setWine($freshWine);
                $cartItem->setQuantity($quantity);
                $cart->addItem($cartItem);
            }
        });

        return $error;
    }

    /**
     * Met a jour la quantite d'un article. Retourne null si OK, ou un message d'erreur.
     */
    public function updateItemQuantity(CartItem $cartItem, int $quantity): ?string
    {
        $quantity = max(1, $quantity);
        $error = null;

        // Transaction avec verrou pessimiste pour vérifier le stock avant mise à jour
        $this->em->wrapInTransaction(function () use ($cartItem, $quantity, &$error): void {
            $freshWine = $this->em->find(Wine::class, $cartItem->getWine()->getId(), LockMode::PESSIMISTIC_READ);
            if ($freshWine === null || !$freshWine->hasEnoughStock($quantity)) {
                $error = 'Stock insuffisant.';
                return;
            }
            $cartItem->setQuantity($quantity);
        });

        return $error;
    }

    /**
     * Supprime un article du panier.
     */
    public function removeItem(CartItem $cartItem): void
    {
        $cart = $cartItem->getCart();
        $cart->removeItem($cartItem);
        $this->em->flush();
    }

    /**
     * Vide entièrement le panier de l'utilisateur ou du visiteur.
     */
    public function clearCart(?User $user): void
    {
        $cart = $this->getCart($user);

        if ($cart) {
            $cart->clear();
            $this->em->flush();
        }
    }
}
