<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Booking\Reservation;
use App\Entity\Order\Order;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailServiceTest extends TestCase
{
    private function makeService(MailerInterface $mailer, ?LoggerInterface $logger = null): EmailService
    {
        return new EmailService($mailer, $logger ?? $this->createStub(LoggerInterface::class));
    }

    private function makeOrder(): Order
    {
        $order = new Order();
        $order->setCustomerEmail('client@example.com');
        $order->generateReference();

        return $order;
    }

    private function makeReservation(): Reservation
    {
        $r = new Reservation();
        $r->setFirstName('Anne');
        $r->setLastName('Leclerc');
        $r->setEmail('anne@example.com');
        $r->setPhone('0600000000');

        return $r;
    }

    // ------- envoi email -------

    public function testSendOrderConfirmationCallsMailerSend(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $this->makeService($mailer)->sendOrderConfirmation($this->makeOrder());
    }

    public function testSendPaymentConfirmationCallsMailerSend(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $this->makeService($mailer)->sendPaymentConfirmation($this->makeOrder());
    }

    public function testSendOrderShippedCallsMailerSend(): void
    {
        $order = $this->makeOrder();
        $order->markAsShipped('TRACK999', 'Colissimo');

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $this->makeService($mailer)->sendOrderShipped($order);
    }

    public function testSendReservationConfirmationCallsMailerSend(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects($this->once())->method('send');

        $this->makeService($mailer)->sendReservationConfirmation($this->makeReservation());
    }

    // ------- gestion TransportException -------

    public function testSendOrderConfirmationCatchesTransportException(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException($this->createStub(TransportExceptionInterface::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $this->makeService($mailer, $logger)->sendOrderConfirmation($this->makeOrder());
    }

    public function testSendPaymentConfirmationCatchesTransportException(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException($this->createStub(TransportExceptionInterface::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $this->makeService($mailer, $logger)->sendPaymentConfirmation($this->makeOrder());
    }

    public function testSendOrderShippedCatchesTransportException(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException($this->createStub(TransportExceptionInterface::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $order = $this->makeOrder();
        $order->markAsShipped('TR123', 'DHL');
        $this->makeService($mailer, $logger)->sendOrderShipped($order);
    }

    public function testSendReservationConfirmationCatchesTransportException(): void
    {
        $mailer = $this->createStub(MailerInterface::class);
        $mailer->method('send')->willThrowException($this->createStub(TransportExceptionInterface::class));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error');

        $this->makeService($mailer, $logger)->sendReservationConfirmation($this->makeReservation());
    }
}
