<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AccountPagesTest extends WebTestCase
{
    public function testAccountDashboard(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte');

        $this->assertResponseIsSuccessful();
    }

    public function testOrdersPage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/commandes');

        $this->assertResponseIsSuccessful();
    }

    public function testReservationsPage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/reservations');

        $this->assertResponseIsSuccessful();
    }

    public function testAddressesPage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/adresses');

        $this->assertResponseIsSuccessful();
    }

    public function testProfilePage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/profil');

        $this->assertResponseIsSuccessful();
    }

    public function testPasswordPage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/mot-de-passe');

        $this->assertResponseIsSuccessful();
    }

    public function testFavoritesPage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/mes-vins');

        $this->assertResponseIsSuccessful();
    }

    public function testAddAddressPage(): void
    {
        $client = static::createClient();
        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $client->loginUser($user);

        $client->request('GET', '/compte/adresses/ajouter');

        $this->assertResponseIsSuccessful();
    }
}
