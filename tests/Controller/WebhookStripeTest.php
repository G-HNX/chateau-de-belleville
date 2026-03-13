<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Order\Order;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels du webhook Stripe.
 *
 * Prérequis : la base de données de test doit exister.
 * La signature HMAC est calculée manuellement avec la clé whsec_test_placeholder
 * définie dans .env.test — même algorithme que le SDK Stripe.
 */
class WebhookStripeTest extends WebTestCase
{
    /** @var int[] IDs des commandes créées pendant le test, supprimées en tearDown */
    private array $createdOrderIds = [];

    protected function tearDown(): void
    {
        if ($this->createdOrderIds !== []) {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            foreach ($this->createdOrderIds as $id) {
                $order = $em->find(Order::class, $id);
                if ($order) {
                    $em->remove($order);
                }
            }
            $em->flush();
        }

        parent::tearDown();
    }

    public function testInvalidSignatureReturns400(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['HTTP_Stripe-Signature' => 'bad_sig', 'CONTENT_TYPE' => 'application/json'],
            '{"type":"payment_intent.succeeded"}'
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testValidSignatureWithUnknownOrderReturns200(): void
    {
        $payload = $this->buildPayload('CB-UNKNOWN-9999');
        $timestamp = time();
        $sigHeader = $this->computeSignature($payload, $timestamp);

        $client = static::createClient();
        $client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['HTTP_Stripe-Signature' => $sigHeader, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('OK', $client->getResponse()->getContent());
    }

    public function testWebhookMarksOrderAsPaid(): void
    {
        $client = static::createClient();
        $order = $this->createTestOrder();
        $this->assertSame(OrderStatus::PENDING, $order->getStatus());

        $payload = $this->buildPayload($order->getReference());
        $timestamp = time();
        $sigHeader = $this->computeSignature($payload, $timestamp);
        $client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['HTTP_Stripe-Signature' => $sigHeader, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertResponseStatusCodeSame(200);

        // Recharger depuis la base pour vérifier le statut
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $updated = $em->find(Order::class, $order->getId());

        $this->assertNotNull($updated);
        $this->assertSame(OrderStatus::PAID, $updated->getStatus());
        $this->assertNotNull($updated->getPaidAt());
    }

    public function testWebhookIsIdempotentForAlreadyPaidOrder(): void
    {
        $client = static::createClient();
        $order = $this->createTestOrder();

        // Marquer comme payée avant que le webhook arrive (doublon de livraison)
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $order->markAsPaid();
        $em->flush();

        $paidAt = $order->getPaidAt();

        $payload = $this->buildPayload($order->getReference());
        $timestamp = time();
        $sigHeader = $this->computeSignature($payload, $timestamp);
        $client->request(
            'POST',
            '/webhook/stripe',
            [],
            [],
            ['HTTP_Stripe-Signature' => $sigHeader, 'CONTENT_TYPE' => 'application/json'],
            $payload
        );

        $this->assertResponseStatusCodeSame(200);

        // La commande doit rester PAID et paidAt inchangé
        $em->clear();
        $updated = $em->find(Order::class, $order->getId());

        $this->assertSame(OrderStatus::PAID, $updated->getStatus());
        $this->assertEqualsWithDelta(
            $paidAt->getTimestamp(),
            $updated->getPaidAt()->getTimestamp(),
            1,
            'paidAt ne doit pas être modifié par un second webhook'
        );
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createTestOrder(): Order
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $order = new Order();
        $order->setCustomerEmail('webhook-test@chateau-test.fr');
        $order->setCustomerFirstName('Test');
        $order->setCustomerLastName('Webhook');
        $order->setBillingAddress([
            'firstName' => 'Test', 'lastName' => 'Webhook',
            'street' => '1 rue du Test', 'zipCode' => '75001',
            'city' => 'Paris', 'country' => 'FR',
        ]);
        $order->setShippingAddress([
            'firstName' => 'Test', 'lastName' => 'Webhook',
            'street' => '1 rue du Test', 'zipCode' => '75001',
            'city' => 'Paris', 'country' => 'FR',
        ]);

        $em->persist($order);
        $em->flush();

        $this->createdOrderIds[] = $order->getId();

        return $order;
    }

    private function buildPayload(string $orderReference): string
    {
        return (string) json_encode([
            'id'     => 'evt_test_' . bin2hex(random_bytes(8)),
            'object' => 'event',
            'type'   => 'payment_intent.succeeded',
            'data'   => [
                'object' => [
                    'id'       => 'pi_test_' . bin2hex(random_bytes(8)),
                    'object'   => 'payment_intent',
                    'amount'   => 1490,
                    'currency' => 'eur',
                    'metadata' => [
                        'order_reference' => $orderReference,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Calcule une signature Stripe valide (même algorithme que le SDK Stripe).
     * Format : t={timestamp},v1={hmac_sha256(secret, "{timestamp}.{payload}")}
     */
    private function computeSignature(string $payload, int $timestamp): string
    {
        $secret = 'whsec_test_placeholder'; // défini dans .env.test
        $sig = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return "t={$timestamp},v1={$sig}";
    }
}