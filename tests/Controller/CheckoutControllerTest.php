<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CheckoutControllerTest extends WebTestCase
{
    public function testCheckoutRedirectsWhenCartEmpty(): void
    {
        $client = static::createClient();
        $client->request('GET', '/commande');

        $this->assertResponseRedirects('/panier');
    }

    public function testCartPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/panier');

        $this->assertResponseIsSuccessful();
    }
}
