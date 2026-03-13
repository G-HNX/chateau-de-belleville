<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order\Order;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

/**
 * Service d'intégration avec l'API Stripe pour les paiements.
 *
 * Gère la création et la récupération des PaymentIntents,
 * ainsi que la vérification des signatures des webhooks Stripe
 * pour sécuriser le traitement des événements de paiement.
 */
class StripeService
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly string $stripeWebhookSecret,
    ) {
        // Initialisation de la clé API Stripe au niveau global du SDK
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Crée un PaymentIntent Stripe pour une commande donnée.
     * Le montant est en centimes, la devise est l'euro.
     *
     * @throws ApiErrorException En cas d'erreur de communication avec Stripe
     */
    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $order->getTotalInCents(),
            'currency' => 'eur',
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_reference' => $order->getReference(),
                'order_id' => $order->getId(),
            ],
            'description' => sprintf('Commande %s - Château de Belleville', $order->getReference()),
        ]);
    }

    /**
     * Récupère un PaymentIntent existant par son identifiant Stripe.
     * Utilisé pour réutiliser un PI existant au lieu d'en créer un nouveau.
     *
     * @throws ApiErrorException En cas d'erreur de communication avec Stripe
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Vérifie la signature d'un webhook Stripe et reconstruit l'événement.
     * Protège contre les requêtes forgées en validant la signature HMAC.
     *
     * @param string $payload   Le corps brut de la requête webhook
     * @param string $sigHeader L'en-tête Stripe-Signature de la requête
     *
     * @throws SignatureVerificationException Si la signature est invalide
     */
    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
    }
}
