<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Order\Cart;
use App\Entity\Order\CartItem;
use App\Entity\User\User;
use App\Service\EmailService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrderServiceTest extends TestCase
{
    private function makeService(
        ?EntityManagerInterface $em = null,
        ?LoggerInterface $logger = null,
        ?EmailService $emailService = null,
    ): OrderService {
        return new OrderService(
            $em ?? $this->createStub(EntityManagerInterface::class),
            $logger ?? $this->createStub(LoggerInterface::class),
            $emailService ?? $this->createStub(EmailService::class),
        );
    }

    private function makeAddress(): array
    {
        return [
            'firstName' => 'Jean',
            'lastName'  => 'Dupont',
            'street'    => '123 rue de la Paix',
            'zipCode'   => '75001',
            'city'      => 'Paris',
            'country'   => 'FR',
        ];
    }

    private function makeEmWithTransaction(?Wine $wineFromFind = null): EntityManagerInterface
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());
        if ($wineFromFind !== null) {
            $em->method('find')->willReturn($wineFromFind);
        }

        return $em;
    }

    // ------- checkCartStock -------

    public function testCheckCartStockReturnsNullWhenStockOk(): void
    {
        $wine = $this->createStub(Wine::class);
        $wine->method('hasEnoughStock')->willReturn(true);

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(2);

        $cart = new Cart();
        $cart->addItem($cartItem);

        $this->assertNull($this->makeService()->checkCartStock($cart));
    }

    public function testCheckCartStockReturnsWineNameWhenInsufficient(): void
    {
        $wine = $this->createStub(Wine::class);
        $wine->method('hasEnoughStock')->willReturn(false);
        $wine->method('getName')->willReturn('Saumur Blanc 2022');

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(5);

        $cart = new Cart();
        $cart->addItem($cartItem);

        $this->assertSame('Saumur Blanc 2022', $this->makeService()->checkCartStock($cart));
    }

    public function testCheckCartStockReturnsNullForEmptyCart(): void
    {
        $this->assertNull($this->makeService()->checkCartStock(new Cart()));
    }

    // ------- createOrderFromCart -------

    public function testCreateOrderFromCartForGuestUsesFormData(): void
    {
        $wine = new Wine();
        $wine->setName('Anjou Rouge');
        $wine->setPriceInCents(1500);
        $wine->setStock(10);
        $wine->setIsActive(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());
        $em->method('find')->willReturn($wine);
        $em->method('persist');
        $em->expects($this->once())->method('remove'); // panier anonyme supprimé

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(2);

        $cart = new Cart();
        $cart->addItem($cartItem);

        $address = $this->makeAddress();
        $formData = [
            'email'           => 'guest@example.com',
            'firstName'       => 'Jean',
            'lastName'        => 'Dupont',
            'phone'           => '0612345678',
            'billingAddress'  => $address,
            'shippingAddress' => $address,
            'notes'           => '',
        ];

        $order = $this->makeService($em, $logger)->createOrderFromCart($cart, null, $formData);

        $this->assertSame('guest@example.com', $order->getCustomerEmail());
        $this->assertSame('Jean', $order->getCustomerFirstName());
        $this->assertSame('Dupont', $order->getCustomerLastName());
        $this->assertTrue($cart->isEmpty());
    }

    public function testCreateOrderFromCartForUserUsesUserData(): void
    {
        $wine = new Wine();
        $wine->setName('Cabernet Franc');
        $wine->setPriceInCents(2000);
        $wine->setStock(5);
        $wine->setIsActive(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());
        $em->method('find')->willReturn($wine);
        $em->method('persist');
        $em->expects($this->never())->method('remove'); // panier utilisateur conservé

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $user = $this->createStub(User::class);
        $user->method('getEmail')->willReturn('user@example.com');
        $user->method('getFirstName')->willReturn('Sophie');
        $user->method('getLastName')->willReturn('Martin');
        $user->method('getPhone')->willReturn('0611223344');

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(1);

        $cart = new Cart();
        $cart->setUser($user);
        $cart->addItem($cartItem);

        $address = $this->makeAddress();
        $order = $this->makeService($em, $logger)->createOrderFromCart($cart, $user, [
            'billingAddress'  => $address,
            'shippingAddress' => $address,
            'notes'           => '',
        ]);

        $this->assertSame('user@example.com', $order->getCustomerEmail());
        $this->assertSame('Sophie', $order->getCustomerFirstName());
        $this->assertSame($user, $order->getCustomer());
    }

    public function testCreateOrderFromCartThrowsOnInsufficientStock(): void
    {
        $wine = new Wine();
        $wine->setName('Rosé épuisé');
        $wine->setPriceInCents(900);
        $wine->setStock(1);
        $wine->setIsActive(true);

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(10); // demande 10, stock = 1

        $cart = new Cart();
        $cart->addItem($cartItem);

        $address = $this->makeAddress();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuffisant/');

        $this->makeService($this->makeEmWithTransaction($wine))->createOrderFromCart($cart, null, [
            'email'           => 'guest@example.com',
            'firstName'       => 'Jean',
            'lastName'        => 'Dupont',
            'phone'           => '0612345678',
            'billingAddress'  => $address,
            'shippingAddress' => $address,
            'notes'           => '',
        ]);
    }

    public function testCreateOrderFromCartDecrementsStock(): void
    {
        $wine = new Wine();
        $wine->setName('Anjou Blanc');
        $wine->setPriceInCents(1200);
        $wine->setStock(10);
        $wine->setIsActive(true);

        $em = $this->makeEmWithTransaction($wine);
        $em->method('persist');

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(3);

        $cart = new Cart();
        $cart->addItem($cartItem);

        $address = $this->makeAddress();
        $this->makeService($em)->createOrderFromCart($cart, null, [
            'email'           => 'a@b.fr',
            'firstName'       => 'A',
            'lastName'        => 'B',
            'phone'           => '0600000000',
            'billingAddress'  => $address,
            'shippingAddress' => $address,
            'notes'           => '',
        ]);

        $this->assertSame(7, $wine->getStock()); // 10 - 3
    }
}
