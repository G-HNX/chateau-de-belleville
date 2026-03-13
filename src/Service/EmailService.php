<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking\Reservation;
use App\Entity\Order\Order;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Service centralisé d'envoi d'emails transactionnels.
 *
 * Gère l'envoi de tous les emails de l'application : confirmations de commande,
 * confirmations de paiement, notifications d'expédition et confirmations de réservation.
 * Les erreurs d'envoi sont loguées mais ne bloquent pas le flux applicatif.
 */
class EmailService
{
    private const FROM_EMAIL = 'noreply@chateaudebelleville.fr';
    private const FROM_NAME = 'Château de Belleville';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Envoie l'email de confirmation de commande au client.
     */
    public function sendOrderConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Commande %s confirmée - Château de Belleville', $order->getReference()))
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context(['order' => $order]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec envoi email confirmation commande', [
                'order' => $order->getReference(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoie l'email de confirmation de paiement au client, après validation par Stripe.
     */
    public function sendPaymentConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Paiement reçu pour la commande %s', $order->getReference()))
            ->htmlTemplate('email/payment_confirmation.html.twig')
            ->context(['order' => $order]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec envoi email confirmation paiement', [
                'order' => $order->getReference(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoie l'email de notification d'expédition au client, avec le suivi éventuel.
     */
    public function sendOrderShipped(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Commande %s expédiée !', $order->getReference()))
            ->htmlTemplate('email/order_shipped.html.twig')
            ->context(['order' => $order]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec envoi email expédition commande', [
                'order' => $order->getReference(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Envoie l'email de confirmation de réservation de dégustation au visiteur.
     */
    public function sendReservationConfirmation(Reservation $reservation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($reservation->getEmail())
            ->subject('Réservation confirmée - Château de Belleville')
            ->htmlTemplate('email/reservation_confirmation.html.twig')
            ->context(['reservation' => $reservation]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->logger->error('Échec envoi email confirmation réservation', [
                'reservation' => $reservation->getId(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
