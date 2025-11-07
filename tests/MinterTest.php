<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../app/core/minter.php';

class MinterTest extends TestCase
{
    public function testFirstMintReturnsBaseWith1(): void
    {
        $m = new Minter('fa');
        $this->assertSame('fa_1', $m->mint());
    }

    public function testCounterIncrementsEachTime(): void
    {
        $m = new Minter('fa');
        $m->mint(); // fa_1
        $m->mint(); // fa_2
        $this->assertSame('fa_3', $m->mint());
    }
}
