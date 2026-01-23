<?php
use PHPUnit\Framework\TestCase;

require __DIR__ . "../app/init.php";

class SmokeTest extends Testcase
{
    public function testConfigLoads()
    {
        $config = new Config();
        $panels = $config->get('panels');

        $this->assertIsArray($panels);
    }

    public function testModelsLoadable(): void
    {
      $this->assertTrue(class_exists('FindingaidModel'));
      $this->assertTrue(class_exists('ComponentModel'));
      $this->assertTrue(class_exists('OverviewModel'));
    }

    public function testBrevityFunction(): void
    {
        $this->assertSame('Hello world', fa_brevity('Hello world', 100));
        $this->assertSame('Hello…', fa_brevity('Hello world', 1));
    }
}
