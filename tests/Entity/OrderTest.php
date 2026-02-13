<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Order\Order;
use App\Entity\Order\OrderItem;
use App\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testCalculateTotals(): void
    {
        $order = new Order();

        $item1 = $this->createMock(OrderItem::class);
        $item1->method('getTotalInCents')->willReturn(1250); // 12.50€
        $order->addItem($item1);

        $item2 = $this->createMock(OrderItem::class);
        $item2->method('getTotalInCents')->willReturn(2900); // 29.00€
        $order->addItem($item2);

        $order->calculateTotals();

        $this->assertSame(4150, $order->getSubtotalInCents());
        $this->assertSame(990, $order->getShippingCostInCents()); // < 150€ → 9.90€
        $this->assertSame(5140, $order->getTotalInCents());
    }

    public function testCalculateTotalsFreeShipping(): void
    {
        $order = new Order();

        $item = $this->createMock(OrderItem::class);
        $item->method('getTotalInCents')->willReturn(16000); // 160€
        $order->addItem($item);

        $order->calculateTotals();

        $this->assertSame(16000, $order->getSubtotalInCents());
        $this->assertSame(0, $order->getShippingCostInCents()); // >= 150€ → gratuit
        $this->assertSame(16000, $order->getTotalInCents());
    }

    public function testMarkAsPaid(): void
    {
        $order = new Order();
        $this->assertSame(OrderStatus::PENDING, $order->getStatus());

        $order->markAsPaid();

        $this->assertSame(OrderStatus::PAID, $order->getStatus());
        $this->assertNotNull($order->getPaidAt());
    }

    public function testMarkAsShipped(): void
    {
        $order = new Order();
        $order->markAsShipped('TRACK123', 'Colissimo');

        $this->assertSame(OrderStatus::SHIPPED, $order->getStatus());
        $this->assertSame('TRACK123', $order->getTrackingNumber());
        $this->assertSame('Colissimo', $order->getCarrier());
        $this->assertNotNull($order->getShippedAt());
    }

    public function testCanBeCancelled(): void
    {
        $order = new Order();
        $this->assertTrue($order->canBeCancelled()); // PENDING

        $order->markAsPaid();
        $this->assertTrue($order->canBeCancelled()); // PAID

        $order->markAsShipped();
        $this->assertFalse($order->canBeCancelled()); // SHIPPED
    }

    public function testStatusTransitions(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::PAID));
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::SHIPPED));
        $this->assertTrue(OrderStatus::PAID->canTransitionTo(OrderStatus::PROCESSING));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::PAID));
    }

    public function testGetFormattedTotal(): void
    {
        $order = new Order();

        $item = $this->createMock(OrderItem::class);
        $item->method('getTotalInCents')->willReturn(4290);
        $order->addItem($item);

        $order->calculateTotals();

        $this->assertStringContainsString('EUR', $order->getFormattedTotal());
    }

    public function testGenerateReference(): void
    {
        $order = new Order();
        $order->generateReference();

        $this->assertNotNull($order->getReference());
        $this->assertStringStartsWith('CB-', $order->getReference());
    }
}
