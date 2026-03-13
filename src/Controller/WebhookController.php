<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\Order\OrderRepository;
use App\Service\EmailService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WebhookController extends AbstractController
{
    #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
    public function stripe(
        Request $request,
        StripeService $stripeService,
        OrderRepository $orderRepository,
        EntityManagerInterface $em,
        EmailService $emailService,
        LoggerInterface $logger,
    ): Response {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('Stripe-Signature', '');

        try {
            $event = $stripeService->constructWebhookEvent($payload, $sigHeader);
        } catch (\Exception $e) {
            $logger->error('Stripe webhook signature verification failed.', [
                'error' => $e->getMessage(),
            ]);

            return new Response('Invalid signature', 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;
            $orderRef = $paymentIntent->metadata->order_reference ?? null;

            if ($orderRef) {
                $order = $orderRepository->findByReference($orderRef);

                // Double garde idempotence : statut PENDING ET paiement non déjà enregistré
                if ($order
                    && $order->getStatus() === \App\Enum\OrderStatus::PENDING
                    && $order->getPaidAt() === null
                ) {
                    $order->markAsPaid();
                    $em->flush();
                    $emailService->sendPaymentConfirmation($order);
                    $logger->info('Order marked as paid.', [
                        'reference' => $orderRef,
                        'stripe_event_id' => $event->id,
                    ]);
                } elseif ($order && $order->getPaidAt() !== null) {
                    $logger->info('Webhook payment_intent.succeeded ignored (already paid).', [
                        'reference' => $orderRef,
                        'stripe_event_id' => $event->id,
                    ]);
                }
            }
        }

        return new Response('OK', 200);
    }
}
