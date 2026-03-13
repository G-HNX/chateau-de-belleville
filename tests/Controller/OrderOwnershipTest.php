<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Order\Order;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests d'ownership sur les pages commandes.
 *
 * Vérifie qu'un utilisateur ne peut pas accéder aux commandes d'un autre,
 * et qu'un guest sans token de session est bloqué.
 *
 * Prérequis : fixtures chargées (php bin/console doctrine:fixtures:load).
 */
class OrderOwnershipTest extends WebTestCase
{
    /** @var int[] IDs des commandes créées pendant le test */
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

    /**
     * Un guest sans token de session est redirigé vers /connexion
     * (Symfony redirige vers le login pour les utilisateurs non authentifiés
     * qui déclenchent une AccessDeniedException).
     */
    public function testGuestCannotAccessPaymentPageWithoutSessionToken(): void
    {
        $client = static::createClient();
        $order = $this->createTestOrder(null);

        $client->request('GET', '/commande/paiement/' . $order->getReference());

        $this->assertResponseRedirects('/connexion');
    }

    public function testGuestCannotAccessConfirmationPageWithoutSessionToken(): void
    {
        $client = static::createClient();
        $order = $this->createTestOrder(null);

        $client->request('GET', '/commande/confirmation/' . $order->getReference());

        $this->assertResponseRedirects('/connexion');
    }

    public function testUserCannotViewAnotherUsersConfirmationPage(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(UserRepository::class);

        $owner = $repo->findByEmail('marie@example.com');
        $other = $repo->findByEmail('client@example.com');
        $this->assertNotNull($owner, 'Fixture manquante — lancez : php bin/console doctrine:fixtures:load');
        $this->assertNotNull($other);

        $order = $this->createTestOrder($owner);

        $client->loginUser($other);
        $client->request('GET', '/commande/confirmation/' . $order->getReference());

        $this->assertResponseStatusCodeSame(403);
    }

    public function testUserCanAccessOwnConfirmationPage(): void
    {
        $client = static::createClient();
        $owner = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $this->assertNotNull($owner, 'Fixture manquante — lancez : php bin/console doctrine:fixtures:load');

        $order = $this->createTestOrder($owner);

        $client->loginUser($owner);
        $client->request('GET', '/commande/confirmation/' . $order->getReference());

        $this->assertResponseIsSuccessful();
    }

    /**
     * Le AccountController retourne 404 (et non 403) quand la commande
     * appartient à un autre utilisateur — c'est intentionnel pour ne pas
     * révéler l'existence de la commande.
     */
    public function testAccountOrderPageHides404ForOtherUsersOrder(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(UserRepository::class);

        $owner = $repo->findByEmail('marie@example.com');
        $other = $repo->findByEmail('client@example.com');
        $this->assertNotNull($owner, 'Fixture manquante — lancez : php bin/console doctrine:fixtures:load');
        $this->assertNotNull($other);

        $order = $this->createTestOrder($owner);

        $client->loginUser($other);
        $client->request('GET', '/compte/commandes/' . $order->getReference());

        // 404 et non 403 : le contrôleur ne révèle pas l'existence de la commande
        $this->assertResponseStatusCodeSame(404);
    }

    // -------------------------------------------------------------------------
    // Helper
    // -------------------------------------------------------------------------

    private function createTestOrder(?User $owner): Order
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $order = new Order();
        $order->setCustomerEmail('ownership-test@chateau-test.fr');
        $order->setCustomerFirstName('Test');
        $order->setCustomerLastName('Ownership');
        $order->setBillingAddress([
            'firstName' => 'Test', 'lastName' => 'Ownership',
            'street' => '1 rue du Test', 'zipCode' => '75001',
            'city' => 'Paris', 'country' => 'FR',
        ]);
        $order->setShippingAddress([
            'firstName' => 'Test', 'lastName' => 'Ownership',
            'street' => '1 rue du Test', 'zipCode' => '75001',
            'city' => 'Paris', 'country' => 'FR',
        ]);

        if ($owner !== null) {
            $order->setCustomer($owner);
        }

        $em->persist($order);
        $em->flush();

        $this->createdOrderIds[] = $order->getId();

        return $order;
    }
}
