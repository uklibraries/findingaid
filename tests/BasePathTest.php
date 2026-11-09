<?php

namespace Tests\Unit;

use App\Config\Config;
use App\Controllers\Findingaid;
use App\Core\Controller;
use PHPUnit\Framework\TestCase;

class BasePathTest extends TestCase
{
    private $saved_config;

    protected function setUp(): void
    {
        $this->saved_config = $GLOBALS['g_config'] ?? null;
    }

    protected function tearDown(): void
    {
        putenv('APP_BASE_PATH');
        $GLOBALS['g_config'] = $this->saved_config;
    }

    private function useBase($base): void
    {
        putenv("APP_BASE_PATH=$base");
        $GLOBALS['g_config'] = new Config();
    }

    public function testBasePathIsOptionalAndNormalized(): void
    {
        $cases = ['' => '', '/' => '', '/review/euk-118/' => '/review/euk-118'];
        foreach ($cases as $base => $expected) {
            $this->useBase($base);
            $this->assertSame($expected, $GLOBALS['g_config']->basePath());
        }
    }

    public function testBasePathRejectsInvalidValues(): void
    {
        $bases = ['relative', '//outside.test', '/a/../b', '/a?q=1', '/a#fragment', "/a\r\nLocation: bad"];
        foreach ($bases as $base) {
            try {
                $this->useBase($base);
                $this->fail('Accepted invalid base path: ' . $base);
            } catch (\RuntimeException $error) {
                $this->assertStringContainsString('APP_BASE_PATH', $error->getMessage());
            }
        }
    }

    public function testAppPathLinksBackIntoExploreUK(): void
    {
        foreach (['', '/review/euk-118'] as $base) {
            $this->useBase($base);
            $controller = new Controller();

            $this->assertSame($base . '/', $controller->appPath('/'));
            $this->assertSame($base . '/?subject_s=Kentucky', $controller->appPath('/?subject_s=Kentucky'));
        }
    }

    public function testLayoutLinksStayBeneathTheBase(): void
    {
        $templates = ['Layouts/application', 'Layouts/suggest_kdl', 'Layouts/suggest_former_kdl'];

        foreach (['', '/review/euk-118'] as $base) {
            $this->useBase($base);
            $layout = (new Findingaid())->layoutEngine();

            foreach ($templates as $template) {
                // app_base comes from the engine, not the render data
                $html = $layout->render(load_template($template), ['title' => 'Example collection']);

                preg_match_all('/(?:href|src|action)="(\/(?!\/)[^"]*)"/', $html, $matches);
                $this->assertNotEmpty($matches[1], $template);
                foreach ($matches[1] as $url) {
                    $this->assertStringStartsWith($base . '/', $url, $template);
                }
                $this->assertStringContainsString('action="' . $base . '/catalog/"', $html, $template);
                // ui.js reads this to recognise ExploreUK search pages beneath the base.
                $this->assertStringContainsString('<meta name="app-base" content="' . $base . '">', $html, $template);
            }
        }
    }
}
