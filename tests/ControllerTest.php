<?php

namespace Tests\Unit;

use App\Core\Controller;
use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    public function testAssetVersionIsASha384DigestOfTheAssetContents(): void
    {
        $path = 'css/extra.css';
        $contents = file_get_contents(implode(DIRECTORY_SEPARATOR, [
            ROOT,
            'public',
            $path,
        ]));
        $expected = 'sha384-' . base64_encode(hash('sha384', $contents, true));

        $this->assertSame($expected, (new Controller())->assetVersion($path));
    }
}
