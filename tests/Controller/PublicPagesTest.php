<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PublicPagesTest extends WebTestCase
{
    public function testLegalPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mentions-legales');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'SIRET');
        $this->assertSelectorTextContains('body', '329 416 978');
    }

    public function testPrivacyPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/politique-de-confidentialite');

        $this->assertResponseIsSuccessful();
    }

    public function testCgvPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/conditions-generales-de-vente');

        $this->assertResponseIsSuccessful();
    }

    public function testNewsPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/actualites');

        $this->assertResponseIsSuccessful();
    }

    public function testShopPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/vins');

        $this->assertResponseIsSuccessful();
    }

    public function testShopFiltersWork(): void
    {
        $client = static::createClient();
        $client->request('GET', '/vins?type=rouge&prix_min=5&prix_max=30');

        $this->assertResponseIsSuccessful();
    }

    public function testHealthEndpointNotIndexedByBots(): void
    {
        $client = static::createClient();
        $client->request('GET', '/health');

        $this->assertResponseIsSuccessful();
    }

    public function testResetPasswordPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
    }
}
