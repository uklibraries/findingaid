<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Config\Config;

class SmokeTest extends TestCase
{
    public function testConfigLoads()
    {
        $config = new Config();
        $panels = $config->get('panels');

        $this->assertIsArray($panels);
    }

    public function testModelsLoadable(): void
    {
        $this->assertTrue(class_exists('App\Models\Findingaid'));
        $this->assertTrue(class_exists('App\Models\Component'));
    }

    public function testBrevityFunction(): void
    {
        $this->assertSame('Hello world', fa_brevity('Hello world', 100));
        $this->assertSame('Hello…', fa_brevity('Hello world', 1));
    }
}
