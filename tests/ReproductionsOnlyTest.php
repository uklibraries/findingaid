<?php

namespace Tests\Unit;

use Mustache_Engine;
use PHPUnit\Framework\TestCase;

class ReproductionsOnlyTest extends TestCase
{
    public function testMicrofilmCsvBuildsAValidLookup(): void
    {
        $output_file = tempnam(sys_get_temp_dir(), 'reproductions-only-');
        $this->assertNotFalse($output_file);

        try {
            $command = implode(' ', array_map('escapeshellarg', [
                PHP_BINARY,
                ROOT . '/exe/build-reproductions-only.php',
                APP . '/Config/microfilm.csv',
                $output_file,
            ]));
            exec($command . ' 2>&1', $output, $exit_code);

            $this->assertSame(0, $exit_code, implode("\n", $output));
            $collections = require $output_file;
            $this->assertIsArray($collections);
            $this->assertNotEmpty($collections);
        } finally {
            unlink($output_file);
        }
    }

    public function testRestrictedFormKeepsReproductions(): void
    {
        $html = (new Mustache_Engine())->render(
            load_template('Findingaid/requests'),
            ['reproductions_only' => true]
        );
        $this->assertStringNotContainsString('id="fa-schedule-retrieval"', $html);
        $this->assertStringNotContainsString('id="fa-save-for-later"', $html);
        $this->assertStringContainsString('id="fa-request-reproductions"', $html);
        $this->assertStringContainsString('name="RequestType" value="Copy"', $html);
        $this->assertStringNotContainsString('cannot be viewed in person', $html);
        $this->assertStringContainsString('id="fa-request-submit"', $html);
    }

    public function testRestrictedCollectionWithoutContentsCanBeRequested(): void
    {
        $html = (new Mustache_Engine())->render(load_template('Findingaid/requests'), [
            'reproductions_only' => true,
            'collection_request' => [
                'id' => 'fa-no-components-request',
                'container_list' => 'Example collection',
                'volume' => '',
                'container' => '',
            ],
        ]);

        $this->assertStringContainsString('id="fa-no-components-request"', $html);
        $this->assertStringContainsString('name="RequestType" value="Copy"', $html);
        $this->assertStringContainsString('id="fa-request-reproductions"', $html);
        $this->assertStringNotContainsString('id="fa-schedule-retrieval"', $html);
        $this->assertStringNotContainsString('id="fa-save-for-later"', $html);
    }

    public function testUnlistedFormRetainsNormalOptions(): void
    {
        $html = (new Mustache_Engine())->render(
            load_template('Findingaid/requests'),
            ['reproductions_only' => false]
        );
        $this->assertStringContainsString('id="fa-schedule-retrieval"', $html);
        $this->assertStringContainsString('id="fa-save-for-later"', $html);
        $this->assertStringContainsString('id="fa-request-reproductions"', $html);
        $this->assertStringContainsString('name="RequestType" value="Loan"', $html);
        $this->assertStringNotContainsString('cannot be viewed in person', $html);
    }
}
