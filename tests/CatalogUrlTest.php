<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Component;

class CatalogUrlTest extends TestCase
{
    public function testPageLevelDaoIsPassedThroughVerbatim(): void
    {
        $this->assertSame(
            '/catalog/xt7x3f4knz7q_1_1',
            Component::catalogUrl('xt7x3f4knz7q_1_1')
        );
    }

    public function testMissingDaoYieldsNull(): void
    {
        $this->assertNull(Component::catalogUrl(null));
    }

    public function testEmptyDaoYieldsNullRatherThanBareCatalogPath(): void
    {
        $this->assertNull(Component::catalogUrl(''));
        $this->assertNull(Component::catalogUrl('   '));
    }

    public function testDaoIsUrlEncoded(): void
    {
        $this->assertSame(
            '/catalog/odd%20id%3F1',
            Component::catalogUrl('odd id?1')
        );
    }
}
