<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order\Cart;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\User\User;
use Doctrine\ORM\EntityManagerInterface;

class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /**
     * Verifie le stock de tous les articles du panier.
     * Retourne le nom du premier vin en rupture, ou null si tout est OK.
     */
    public function checkCartStock(Cart $cart): ?string
    {
        foreach ($cart->getItems() as $item) {
            if (!$item->getWine()->hasEnoughStock($item->getQuantity())) {
                return $item->getWine()->getName();
            }
        }

        return null;
    }

    /**
     * Cree une commande a partir du panier et des donnees du formulaire.
     */
    public function createOrderFromCart(
        Cart $cart,
        ?User $user,
        array $formData,
    ): Order {
        $order = new Order();

        if ($user) {
            $order->setCustomer($user);
            $order->setCustomerEmail($user->getEmail());
            $order->setCustomerFirstName($user->getFirstName());
            $order->setCustomerLastName($user->getLastName());
            $order->setCustomerPhone($user->getPhone());
        } else {
            $order->setCustomerEmail($formData['email']);
            $order->setCustomerFirstName($formData['firstName']);
            $order->setCustomerLastName($formData['lastName']);
            $order->setCustomerPhone($formData['phone']);
        }

        $order->setBillingAddress($formData['billingAddress']);
        $order->setShippingAddress($formData['shippingAddress']);
        $order->setCustomerNotes($formData['notes']);

        foreach ($cart->getItems() as $cartItem) {
            $order->addItem(OrderItem::createFromCartItem($cartItem));
            $cartItem->getWine()->decrementStock($cartItem->getQuantity());
        }

        $order->calculateTotals();

        $this->em->persist($order);

        $cart->clear();

        $this->em->flush();

        return $order;
    }
}
