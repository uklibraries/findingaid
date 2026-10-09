<?php

namespace Tests\Unit;

use App\Models\Component;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SimpleXMLElement;

class ComponentTest extends TestCase
{
    private function title(string $did): string
    {
        // The full Component.php constructor needs an XML file so make a small fixture instead
        $component = (new ReflectionClass(Component::class))->newInstanceWithoutConstructor();
        $component->xml = new SimpleXMLElement('<c id="ref1" level="item"><did>' . $did . '</did></c>');
        return $component->title();
    }

    public function testTitleJoinsTheUnittitleAndUnitdate(): void
    {
        $this->assertSame('Letters, 1906', $this->title('<unittitle>Letters</unittitle><unitdate>1906</unitdate>'));
    }

    public function testTitleIsJustTheUnittitleWithoutAUnitdate(): void
    {
        $this->assertSame('Letters', $this->title('<unittitle>Letters</unittitle>'));
    }

    public function testTitleIsJustTheUnitdateWithoutAUnittitle(): void
    {
        $this->assertSame('1980 March', $this->title('<unitdate>1980 March</unitdate>'));
    }

    public function testEmptyUnittitleLeavesNoLeadingComma(): void
    {
        $this->assertSame('1906', $this->title('<unittitle/><unitdate>1906</unitdate>'));
    }

    public function testWhitespaceOnlyUnittitleLeavesNoLeadingComma(): void
    {
        $this->assertSame('1906', $this->title('<unittitle> </unittitle><unitdate>1906</unitdate>'));
    }

    public function testEmptyUnitdateLeavesNoTrailingComma(): void
    {
        $this->assertSame('Letters', $this->title('<unittitle>Letters</unittitle><unitdate/>'));
    }

    public function testEveryUnitdateIsListed(): void
    {
        $this->assertSame(
            'Headhouse ruins, 1998, undated',
            $this->title(
                '<unittitle>Headhouse ruins</unittitle>'
                . '<unitdate type="inclusive">1998</unitdate><unitdate>undated</unitdate>'
            )
        );
    }
}
