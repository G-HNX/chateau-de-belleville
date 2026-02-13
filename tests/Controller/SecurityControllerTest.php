<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form');
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/connexion');

        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'invalid@example.com',
            '_password' => 'wrongpassword',
        ]);

        $client->submit($form);
        $client->followRedirect();

        $this->assertSelectorExists('.flash-error, .alert-danger, [role="alert"]');
    }

    public function testAccountRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/compte');

        $this->assertResponseRedirects('/connexion');
    }

    public function testAdminRequiresAdminRole(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects('/connexion');
    }

    public function testRegisterPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/inscription');

        $this->assertResponseIsSuccessful();
    }
}
