<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Catalog\Wine;
use App\Enum\WineType;
use PHPUnit\Framework\TestCase;

class WineTest extends TestCase
{
    public function testPriceConversion(): void
    {
        $wine = new Wine();
        $wine->setPriceInCents(1250);

        $this->assertSame(12.5, $wine->getPrice());
    }

    public function testSetPriceFromFloat(): void
    {
        $wine = new Wine();
        $wine->setPrice(14.50);

        $this->assertSame(1450, $wine->getPriceInCents());
    }

    public function testStockManagement(): void
    {
        $wine = new Wine();
        $wine->setStock(10);

        $this->assertTrue($wine->isInStock());
        $this->assertTrue($wine->hasEnoughStock(5));
        $this->assertFalse($wine->hasEnoughStock(15));

        $wine->decrementStock(3);
        $this->assertSame(7, $wine->getStock());

        $wine->incrementStock(2);
        $this->assertSame(9, $wine->getStock());
    }

    public function testDecrementStockNeverNegative(): void
    {
        $wine = new Wine();
        $wine->setStock(2);
        $wine->decrementStock(10);

        $this->assertSame(0, $wine->getStock());
    }

    public function testIsAvailable(): void
    {
        $wine = new Wine();
        $wine->setIsActive(true);
        $wine->setStock(5);

        $this->assertTrue($wine->isAvailable());

        $wine->setStock(0);
        $this->assertFalse($wine->isAvailable());

        $wine->setStock(5);
        $wine->setIsActive(false);
        $this->assertFalse($wine->isAvailable());
    }

    public function testFormattedPrice(): void
    {
        $wine = new Wine();
        $wine->setPriceInCents(1250);

        $this->assertSame('12,50 EUR', $wine->getFormattedPrice());
    }
}
