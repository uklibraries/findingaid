<?php

namespace Tests\Unit;

use Mustache_Engine;
use PHPUnit\Framework\TestCase;

class CollectionRequestTest extends TestCase
{
    public function testCollectionRequestRemainsAvailableOutsideTheCollapsedPanel(): void
    {
        $html = (new Mustache_Engine())->render(load_template('Findingaid/requests'), [
            'collection_request' => [
                'id' => 'fa-no-components-request',
                'container_list' => 'Example collection',
                'volume' => '',
                'container' => '',
            ],
        ]);
        $document = new \DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        // These classes are the hooks requests.js uses to create the collection button.
        $targets = $xpath->query(
            '//input[contains(concat(" ", normalize-space(@class), " "), " fa-requestable ")]'
            . '[contains(concat(" ", normalize-space(@class), " "), " fa-collection ")]'
        );

        $this->assertCount(1, $targets, 'The collection must have one selectable request target.');
        $this->assertCount(0, $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " js-accordion__panel ")]',
            $targets->item(0)
        ), 'Collapsing the request options must not hide the collection button.');
    }

    public function testNoCollectionRequestTargetWithoutTheFallback(): void
    {
        $html = (new Mustache_Engine())->render(load_template('Findingaid/requests'), [
            'collection_request' => false,
        ]);

        $this->assertStringNotContainsString('fa-requestable', $html);
    }
}
