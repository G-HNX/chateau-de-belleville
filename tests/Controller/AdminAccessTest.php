<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminAccessTest extends WebTestCase
{
    public function testAdminPageRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/connexion');
    }

    public function testAdminPageForbiddenForRegularUser(): void
    {
        $client = static::createClient();

        $user = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $this->assertNotNull($user, 'Fixture manquante — lancez : php bin/console doctrine:fixtures:load');

        $client->loginUser($user);
        $client->request('GET', '/admin');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAdminPageAccessibleForAdmin(): void
    {
        $client = static::createClient();

        $admin = static::getContainer()->get(UserRepository::class)->findByEmail('gabriel.heneaux@gmail.com');
        $this->assertNotNull($admin, 'Fixture manquante — lancez : php bin/console doctrine:fixtures:load');

        $client->loginUser($admin);
        $client->request('GET', '/admin');

        $this->assertResponseIsSuccessful();
    }
}
