<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Order\Order;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct(
        private readonly string $stripeSecretKey,
        private readonly string $stripeWebhookSecret,
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $order->getTotalInCents(),
            'currency' => 'eur',
            'metadata' => [
                'order_reference' => $order->getReference(),
                'order_id' => $order->getId(),
            ],
            'description' => sprintf('Commande %s - Château de Belleville', $order->getReference()),
        ]);
    }

    public function constructWebhookEvent(string $payload, string $sigHeader): \Stripe\Event
    {
        return Webhook::constructEvent($payload, $sigHeader, $this->stripeWebhookSecret);
    }
}
