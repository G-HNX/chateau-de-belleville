<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomepage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Château de Belleville');
    }

    public function testWineIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/vins');

        $this->assertResponseIsSuccessful();
    }

    public function testTastingIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/degustations');

        $this->assertResponseIsSuccessful();
    }

    public function testContact(): void
    {
        $client = static::createClient();
        $client->request('GET', '/contact');

        $this->assertResponseIsSuccessful();
    }

    public function testDomain(): void
    {
        $client = static::createClient();
        $client->request('GET', '/domaine');

        $this->assertResponseIsSuccessful();
    }

    public function testLoginPage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/connexion');

        $this->assertResponseIsSuccessful();
    }
}
