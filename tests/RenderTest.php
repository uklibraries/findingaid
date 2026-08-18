<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RenderTest extends TestCase
{
    private function render($extref): string
    {
        return fa_render(simplexml_load_string("<p>$extref</p>"));
    }

    public function testExtrefStaysInTheSameTabByDefault(): void
    {
        $html = $this->render('<extref href="https://example.org/">the guide</extref>');

        $this->assertStringContainsString('<span class="show-for-sr">(external link)</span>', $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    public function testExtrefOptsIntoANewTabWithShowNew(): void
    {
        $html = $this->render('<extref href="https://example.org/" show="new">the guide</extref>');

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringContainsString(
            '<span class="show-for-sr">(external link, opens in a new tab)</span>',
            $html
        );
    }

    /* show="replace" is XLink for "stay here", and any value the spec leaves
     * unconstrained should fail safe the same way.
     */
    public function testOnlyShowNewOpensANewTab(): void
    {
        foreach (['replace', 'embed', 'other', 'none', 'nonsense'] as $show) {
            $html = $this->render("<extref href=\"https://example.org/\" show=\"$show\">the guide</extref>");
            $this->assertStringNotContainsString('target="_blank"', $html, "show=\"$show\" opened a new tab");
        }
    }

    public function testXlinkNamespacedAttributesAreHonoured(): void
    {
        $html = $this->render(
            '<extref xmlns:xlink="http://www.w3.org/1999/xlink"'
            . ' xlink:href="https://example.org/" xlink:show="new">the guide</extref>'
        );

        $this->assertStringContainsString('href="https://example.org/"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }

    /* Fragments lose <ead> xmlns:xlink, so it only renders because libxml recovers.
    * This test ensures that if we fix behavior, we don't accidentally lose the links.
    * If error assertion fails, upstream fixed fragments, and this test is obsolete.
    */
    public function testXlinkAttributesAreHonouredWhenTheNamespaceIsUndeclared(): void
    {
        $previous = libxml_use_internal_errors(true);
        try {
            $html = $this->render(
                '<extref xlink:href="https://example.org/" xlink:show="new">the guide</extref>'
            );
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $this->assertNotSame([], $errors, 'expected libxml to report the undeclared xlink prefix');
        $this->assertStringContainsString('href="https://example.org/"', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }

    public function testExtrefWithoutLinkTextRendersNothing(): void
    {
        $html = $this->render('<extref actuate="onLoad" show="embed" href="http://example.org/seal.gif"/>');

        $this->assertSame('', $html);
    }

    public function testExtrefWithoutHrefFallsBackToItsText(): void
    {
        $html = $this->render('<extref>text only</extref>');

        $this->assertSame('text only', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function testHrefAndTextFromTheEadAreEscaped(): void
    {
        $html = $this->render('<extref href="https://example.org/?a=1&amp;b=&quot;x&quot;">Smith &amp; Co</extref>');

        $this->assertStringContainsString('href="https://example.org/?a=1&amp;b=&quot;x&quot;"', $html);
        $this->assertStringContainsString('Smith &amp; Co', $html);
    }
}
