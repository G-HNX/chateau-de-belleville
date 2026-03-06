<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Catalog\Wine;
use App\Entity\Order\Cart;
use App\Entity\Order\CartItem;
use App\Entity\User\User;
use App\Repository\Order\CartRepository;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CartServiceTest extends TestCase
{
    private function makeService(
        ?CartRepository $cartRepo = null,
        ?EntityManagerInterface $em = null,
        ?RequestStack $requestStack = null,
    ): CartService {
        return new CartService(
            $cartRepo ?? $this->createStub(CartRepository::class),
            $em ?? $this->createStub(EntityManagerInterface::class),
            $requestStack ?? $this->createStub(RequestStack::class),
        );
    }

    private function makeEmWithTransaction(): EntityManagerInterface
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(fn(callable $fn) => $fn());

        return $em;
    }

    // ------- getCart -------

    public function testGetCartWithUserSearchesByUser(): void
    {
        $cart = new Cart();
        $user = $this->createStub(User::class);

        $cartRepo = $this->createMock(CartRepository::class);
        $cartRepo->expects($this->once())->method('findByUser')->with($user)->willReturn($cart);

        $this->assertSame($cart, $this->makeService(cartRepo: $cartRepo)->getCart($user));
    }

    public function testGetCartWithoutUserSearchesBySessionId(): void
    {
        $cart = new Cart();

        $session = $this->createStub(SessionInterface::class);
        $session->method('getId')->willReturn('session-abc123');

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $cartRepo = $this->createMock(CartRepository::class);
        $cartRepo->expects($this->once())->method('findBySessionId')->with('session-abc123')->willReturn($cart);

        $this->assertSame($cart, $this->makeService(cartRepo: $cartRepo, requestStack: $requestStack)->getCart(null));
    }

    // ------- addWine -------

    public function testAddWineUnavailableReturnsError(): void
    {
        $wine = $this->createStub(Wine::class);
        $wine->method('isAvailable')->willReturn(false);

        $this->assertSame('Ce vin n\'est pas disponible.', $this->makeService()->addWine(null, $wine, 1));
    }

    public function testAddWineStockInsufficientReturnsError(): void
    {
        $freshWine = $this->createStub(Wine::class);
        $freshWine->method('isAvailable')->willReturn(true);
        $freshWine->method('hasEnoughStock')->willReturn(false);
        $freshWine->method('getId')->willReturn(1);

        $em = $this->makeEmWithTransaction();
        $em->method('find')->willReturn($freshWine);
        $em->method('persist');

        $cartRepo = $this->createStub(CartRepository::class);
        $cartRepo->method('findByUser')->willReturn(null);

        $wine = $this->createStub(Wine::class);
        $wine->method('isAvailable')->willReturn(true);
        $wine->method('getId')->willReturn(1);

        $user = $this->createStub(User::class);
        $result = $this->makeService(cartRepo: $cartRepo, em: $em)->addWine($user, $wine, 5);

        $this->assertSame('Stock insuffisant.', $result);
    }

    public function testAddWineSuccessReturnsNull(): void
    {
        $freshWine = $this->createStub(Wine::class);
        $freshWine->method('isAvailable')->willReturn(true);
        $freshWine->method('hasEnoughStock')->willReturn(true);
        $freshWine->method('getId')->willReturn(1);

        $em = $this->makeEmWithTransaction();
        $em->method('find')->willReturn($freshWine);
        $em->method('persist');

        $cartRepo = $this->createStub(CartRepository::class);
        $cartRepo->method('findByUser')->willReturn(null);

        $wine = $this->createStub(Wine::class);
        $wine->method('isAvailable')->willReturn(true);
        $wine->method('getId')->willReturn(1);

        $user = $this->createStub(User::class);
        $result = $this->makeService(cartRepo: $cartRepo, em: $em)->addWine($user, $wine, 1);

        $this->assertNull($result);
    }

    public function testAddWineIncreasesQuantityForExistingItem(): void
    {
        $freshWine = $this->createStub(Wine::class);
        $freshWine->method('isAvailable')->willReturn(true);
        $freshWine->method('hasEnoughStock')->willReturn(true);
        $freshWine->method('getId')->willReturn(42);

        $em = $this->makeEmWithTransaction();
        $em->method('find')->willReturn($freshWine);
        $em->method('persist');

        $existingItem = new CartItem();
        $existingItem->setWine($freshWine);
        $existingItem->setQuantity(2);

        $cart = new Cart();
        $cart->addItem($existingItem);

        $cartRepo = $this->createStub(CartRepository::class);
        $cartRepo->method('findByUser')->willReturn($cart);

        $wine = $this->createStub(Wine::class);
        $wine->method('isAvailable')->willReturn(true);
        $wine->method('getId')->willReturn(42);

        $user = $this->createStub(User::class);
        $this->makeService(cartRepo: $cartRepo, em: $em)->addWine($user, $wine, 3);

        $this->assertSame(5, $existingItem->getQuantity()); // 2 + 3
    }

    // ------- updateItemQuantity -------

    public function testUpdateItemQuantityStockInsufficientReturnsError(): void
    {
        $wine = $this->createStub(Wine::class);
        $wine->method('hasEnoughStock')->willReturn(false);
        $wine->method('getId')->willReturn(1);

        $freshWine = $this->createStub(Wine::class);
        $freshWine->method('hasEnoughStock')->willReturn(false);

        $em = $this->makeEmWithTransaction();
        $em->method('find')->willReturn($freshWine);

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(1);

        $this->assertSame('Stock insuffisant.', $this->makeService(em: $em)->updateItemQuantity($cartItem, 100));
    }

    public function testUpdateItemQuantitySuccessFlushesAndUpdates(): void
    {
        $wine = $this->createStub(Wine::class);
        $wine->method('hasEnoughStock')->willReturn(true);
        $wine->method('getId')->willReturn(1);

        $freshWine = $this->createStub(Wine::class);
        $freshWine->method('hasEnoughStock')->willReturn(true);

        $em = $this->makeEmWithTransaction();
        $em->method('find')->willReturn($freshWine);

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cartItem->setQuantity(1);

        $result = $this->makeService(em: $em)->updateItemQuantity($cartItem, 3);

        $this->assertNull($result);
        $this->assertSame(3, $cartItem->getQuantity());
    }

    // ------- removeItem -------

    public function testRemoveItemRemovesFromCartAndFlushes(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $cart = new Cart();
        $wine = $this->createStub(Wine::class);

        $cartItem = new CartItem();
        $cartItem->setWine($wine);
        $cart->addItem($cartItem);

        $this->makeService(em: $em)->removeItem($cartItem);

        $this->assertTrue($cart->isEmpty());
    }
}
