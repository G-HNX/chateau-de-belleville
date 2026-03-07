<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Customer\Address;
use App\Entity\User\User;
use App\Repository\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AddressOwnershipTest extends WebTestCase
{
    /** @var int[] */
    private array $createdAddressIds = [];

    protected function tearDown(): void
    {
        if ($this->createdAddressIds !== []) {
            $em = static::getContainer()->get(EntityManagerInterface::class);
            foreach ($this->createdAddressIds as $id) {
                $address = $em->find(Address::class, $id);
                if ($address) {
                    $em->remove($address);
                }
            }
            $em->flush();
        }

        parent::tearDown();
    }

    public function testEditAddressRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/compte/adresses/1/modifier');

        $this->assertResponseRedirects('/connexion');
    }

    public function testEditAddressForbiddenForOtherUser(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(UserRepository::class);

        $owner = $repo->findByEmail('client@example.com');
        $other = $repo->findByEmail('marie@example.com');
        $this->assertNotNull($owner);
        $this->assertNotNull($other);

        $address = $this->createTestAddress($owner);

        $client->loginUser($other);
        $client->request('GET', '/compte/adresses/' . $address->getId() . '/modifier');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditAddressAllowedForOwner(): void
    {
        $client = static::createClient();
        $owner = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $this->assertNotNull($owner);

        $address = $this->createTestAddress($owner);

        $client->loginUser($owner);
        $client->request('GET', '/compte/adresses/' . $address->getId() . '/modifier');

        $this->assertResponseIsSuccessful();
    }

    public function testDeleteAddressForbiddenForOtherUser(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(UserRepository::class);

        $owner = $repo->findByEmail('client@example.com');
        $other = $repo->findByEmail('marie@example.com');
        $this->assertNotNull($owner);
        $this->assertNotNull($other);

        $address = $this->createTestAddress($owner);

        $client->loginUser($other);
        $client->request('POST', '/compte/adresses/' . $address->getId() . '/supprimer', [
            '_token' => 'fake_token',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteAddressRequiresCsrfToken(): void
    {
        $client = static::createClient();
        $owner = static::getContainer()->get(UserRepository::class)->findByEmail('client@example.com');
        $this->assertNotNull($owner);

        $address = $this->createTestAddress($owner);

        $client->loginUser($owner);
        $client->request('POST', '/compte/adresses/' . $address->getId() . '/supprimer', [
            '_token' => 'invalid_csrf_token',
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAddressListRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/compte/adresses');

        $this->assertResponseRedirects('/connexion');
    }

    private function createTestAddress(User $user): Address
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $address = new Address();
        $address->setUser($user);
        $address->setLabel('Test');
        $address->setFirstName('Test');
        $address->setLastName('Address');
        $address->setStreet('1 rue du Test');
        $address->setPostalCode('75001');
        $address->setCity('Paris');
        $address->setCountry('FR');

        $em->persist($address);
        $em->flush();

        $this->createdAddressIds[] = $address->getId();

        return $address;
    }
}
