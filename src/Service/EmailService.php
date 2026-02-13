<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Booking\Reservation;
use App\Entity\Order\Order;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private const FROM_EMAIL = 'chateaudebelleville@gmail.com';
    private const FROM_NAME = 'Château de Belleville';

    public function __construct(
        private readonly MailerInterface $mailer,
    ) {}

    public function sendOrderConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Commande %s confirmée - Château de Belleville', $order->getReference()))
            ->htmlTemplate('email/order_confirmation.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }

    public function sendPaymentConfirmation(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Paiement reçu pour la commande %s', $order->getReference()))
            ->htmlTemplate('email/payment_confirmation.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }

    public function sendOrderShipped(Order $order): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($order->getCustomerEmail())
            ->subject(sprintf('Commande %s expédiée !', $order->getReference()))
            ->htmlTemplate('email/order_shipped.html.twig')
            ->context(['order' => $order]);

        $this->mailer->send($email);
    }

    public function sendReservationConfirmation(Reservation $reservation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address(self::FROM_EMAIL, self::FROM_NAME))
            ->to($reservation->getEmail())
            ->subject('Réservation confirmée - Château de Belleville')
            ->htmlTemplate('email/reservation_confirmation.html.twig')
            ->context(['reservation' => $reservation]);

        $this->mailer->send($email);
    }
}
