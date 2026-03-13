<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Order\Cart;
use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Entity\User\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des commandes.
 *
 * Orchestre la transformation d'un panier en commande : vérification du stock,
 * création des articles de commande, décrémentation du stock avec verrou pessimiste,
 * et suppression du panier anonyme après commande.
 */
class OrderService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly EmailService $emailService,
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
     * Persiste les modifications d'une commande existante en base de données.
     */
    public function save(Order $order): void
    {
        $this->em->flush();
    }

    /**
     * Cree une commande a partir du panier et des donnees du formulaire.
     *
     * @throws \RuntimeException si un vin est en rupture de stock au moment du checkout
     */
    public function createOrderFromCart(
        Cart $cart,
        ?User $user,
        array $formData,
        ?\DateTimeInterface $birthDate = null,
    ): Order {
        $order = new Order();

        // Remplissage des informations client depuis le compte utilisateur ou le formulaire
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
        $order->setCustomerBirthDate($birthDate);

        // Transaction avec verrous pessimistes pour garantir la cohérence du stock
        $this->em->wrapInTransaction(function () use ($cart, $order): void {
            foreach ($cart->getItems() as $cartItem) {
                // Lock pessimiste : empêche une double-vente si deux commandes sont simultanées
                $wine = $this->em->find(Wine::class, $cartItem->getWine()->getId(), LockMode::PESSIMISTIC_WRITE);
                if ($wine === null || !$wine->hasEnoughStock($cartItem->getQuantity())) {
                    throw new \RuntimeException(sprintf(
                        'Stock insuffisant pour "%s". Veuillez mettre à jour votre panier.',
                        $cartItem->getWine()->getName()
                    ));
                }
                $order->addItem(OrderItem::createFromCartItem($cartItem));
                $wine->decrementStock($cartItem->getQuantity());
            }

            $order->calculateTotals();
            $this->em->persist($order);
            $cart->clear();

            // Supprime le panier anonyme (sans utilisateur) pour éviter l'accumulation en base
            if ($cart->getUser() === null) {
                $this->em->remove($cart);
            }
        });

        // Journalisation de la commande créée (email masqué en prod pour RGPD)
        $this->logger->info('Order created.', [
            'reference'      => $order->getReference(),
            'customer_email' => $order->getCustomerEmail(),
            'total_cents'    => $order->getTotalInCents(),
            'items_count'    => $order->getItems()->count(),
        ]);

        // L'email de confirmation est envoyé par le WebhookController
        // après réception du paiement Stripe (sendPaymentConfirmation)

        return $order;
    }
}
